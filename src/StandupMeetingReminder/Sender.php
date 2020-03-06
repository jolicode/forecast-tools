<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\StandupMeetingReminder;

use App\DataSelector\ForecastDataSelector;
use App\DataSelector\SlackDataSelector;
use App\Entity\StandupMeetingReminder;
use App\Repository\ForecastAccountRepository;
use App\Repository\StandupMeetingReminderRepository;

class Sender
{
    private $forecastAccountRepository;
    private $forecastDataSelector;
    private $slackDataSelector;
    private $standupMeetingReminderRepository;

    public function __construct(StandupMeetingReminderRepository $standupMeetingReminderRepository, ForecastAccountRepository $forecastAccountRepository, ForecastDataSelector $forecastDataSelector, SlackDataSelector $slackDataSelector)
    {
        $this->standupMeetingReminderRepository = $standupMeetingReminderRepository;
        $this->forecastAccountRepository = $forecastAccountRepository;
        $this->forecastDataSelector = $forecastDataSelector;
        $this->slackDataSelector = $slackDataSelector;
    }

    public function send()
    {
        $time = (new \DateTime())->format('H:i');
        $standupMeetingReminders = $this->standupMeetingReminderRepository->findByTime($time);
        $standupMeetingRemindersCount = 0;

        foreach ($standupMeetingReminders as $standupMeetingReminder) {
            $this->sendStandupMeetingReminder($standupMeetingReminder);
            ++$standupMeetingRemindersCount;
        }

        return $standupMeetingRemindersCount;
    }

    private function sendStandupMeetingReminder(StandupMeetingReminder $standupMeetingReminder)
    {
        $body = $this->buildBody($standupMeetingReminder);
        $client = \JoliCode\Slack\ClientFactory::create($standupMeetingReminder->getSlackTeam()->getAccessToken());
        $client->chatPostMessage([
            'channel' => $standupMeetingReminder->getChannelId(),
            'blocks' => json_encode($body)
        ]);
    }

    private function buildBody(StandupMeetingReminder $standupMeetingReminder)
    {
        $forecastAccounts = $this->forecastAccountRepository->findBySlackTeamId(
            $standupMeetingReminder->getSlackTeam()->getTeamId()
        );
        $members = [];
        $ping = [];

        // find emails from the Forecast
        foreach ($forecastAccounts as $forecastAccount) {
            $this->forecastDataSelector->setForecastAccount($forecastAccount);
            $people = $this->forecastDataSelector->getPeopleById();
            $assignments = $this->forecastDataSelector->getAssignments(new \DateTime('today'), new \DateTime('tomorrow'));

            foreach ($assignments as $assignment) {
                if (in_array($assignment->getProjectId(), $standupMeetingReminder->getForecastProjects())) {
                    $memberName = sprintf(
                        '%s %s',
                        $people[$assignment->getPersonId()]->getFirstName(),
                        $people[$assignment->getPersonId()]->getLastName()
                    );
                    $members[$people[$assignment->getPersonId()]->getEmail()] = $memberName;
                }
            }
        }

        // find people from Slack
        $slackUsers = $this->slackDataSelector->getUsersByEmail($standupMeetingReminder->getSlackTeam());
        ksort($members);

        foreach ($members as $email => $memberName) {
            if (isset($slackUsers[$email])) {
                $ping[] = sprintf('<@%s>', $slackUsers[$email]);
            } else {
                $ping[] = $memberName;
            }
        }

        // format a string to ping the lucky winners
        $ping = array_unique($ping);

        if (count($ping) > 1) {
            $lastPing = ' and ' . array_pop($ping);
        } else {
            $lastPing = '';
        }

        $ping = implode(', ', $ping) . $lastPing;

        return [
            [
                'type' => 'section',
                'text' => [
                    'type' => 'plain_text',
                    'text' => sprintf("🕘 It's time for the standup meeting!\n Today's participants: %s", $ping),
                    'emoji' => true,
                ],
            ],
        ];
    }
}
