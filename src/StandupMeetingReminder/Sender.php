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
            try {
                $this->sendStandupMeetingReminder($standupMeetingReminder);
            } catch (\Exception $e) {
                // silence
            }

            ++$standupMeetingRemindersCount;
        }

        return $standupMeetingRemindersCount;
    }

    private function sendStandupMeetingReminder(StandupMeetingReminder $standupMeetingReminder)
    {
        $body = $this->buildBody($standupMeetingReminder);

        if (null !== $body) {
            $client = \JoliCode\Slack\ClientFactory::create($standupMeetingReminder->getSlackTeam()->getAccessToken());
            $client->chatPostMessage([
                'channel' => $standupMeetingReminder->getChannelId(),
                'blocks' => json_encode($body),
            ]);
        }
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
            $placeholders = $this->forecastDataSelector->getPlaceholdersById();
            $today = new \DateTime('today');
            $assignments = $this->forecastDataSelector->getAssignments($today, new \DateTime('tomorrow'));
            $assignments = array_values(array_filter($assignments, function ($assignment) use ($today) {
                return $assignment->getStartDate()->format('Y-m-d') <= $today->format('Y-m-d') && $assignment->getEndDate()->format('Y-m-d') >= $today->format('Y-m-d');
            }));

            foreach ($assignments as $assignment) {
                if (\in_array((string) $assignment->getProjectId(), $standupMeetingReminder->getForecastProjects(), true)) {
                    if ($assignment->getPersonId()) {
                        $members[$people[$assignment->getPersonId()]->getEmail()] = $memberName = sprintf(
                            '%s %s',
                            $people[$assignment->getPersonId()]->getFirstName(),
                            $people[$assignment->getPersonId()]->getLastName()
                        );
                    } elseif ($assignment->getPlaceholderId()) {
                        $members[$assignment->getPlaceholderId()] = $memberName = $placeholders[$assignment->getPlaceholderId()]->getName();
                    }
                }
            }
        }

        if (0 === \count($members)) {
            // do not ping when noone works on the project
            return null;
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

        if (\count($ping) > 1) {
            $lastPing = ' and ' . array_pop($ping);
            $ping = implode(', ', $ping) . $lastPing;
            $message = sprintf("🕘 It's time for the stand-up meeting!\nToday's participants: %s", $ping);
        } else {
            $message = sprintf('🕘 It should be time for the stand-up meeting, but %s is the only one working on the project today. Cheers up!', array_shift($ping));
        }

        return [
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => $message,
                ],
            ],
        ];
    }
}
