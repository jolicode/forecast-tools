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
use Bugsnag\Client;

class Sender
{
    public function __construct(private readonly StandupMeetingReminderRepository $standupMeetingReminderRepository, private readonly ForecastAccountRepository $forecastAccountRepository, private readonly ForecastDataSelector $forecastDataSelector, private readonly SlackDataSelector $slackDataSelector, private readonly Client $bugsnagClient)
    {
    }

    public function send(): int
    {
        $time = (new \DateTime())->format('H:i');
        $standupMeetingReminders = $this->standupMeetingReminderRepository->findByTime($time);
        $standupMeetingRemindersCount = 0;

        foreach ($standupMeetingReminders as $standupMeetingReminder) {
            try {
                $this->sendStandupMeetingReminder($standupMeetingReminder);
            } catch (\Exception $e) {
                // silence
                $this->bugsnagClient->notifyException($e, function ($report) use ($standupMeetingReminder): void {
                    $report->setMetaData([
                        'standupMeetingReminder' => $standupMeetingReminder->getId(),
                    ]);
                });
            }

            ++$standupMeetingRemindersCount;
        }

        return $standupMeetingRemindersCount;
    }

    private function sendStandupMeetingReminder(StandupMeetingReminder $standupMeetingReminder): void
    {
        $participants = $this->findParticipants($standupMeetingReminder);

        if (null === $participants) {
            return;
        }

        // format a string to ping the lucky winners
        if (\count($participants) > 1) {
            $lastParticipant = ' and ' . array_pop($participants);
            $participants = implode(', ', $participants) . $lastParticipant;
            $message = sprintf("🕘 It's time for the stand-up meeting!\nToday's participants: %s", $participants);
        } else {
            $message = sprintf('🕘 It should be time for the stand-up meeting, but %s is the only one working on the project today. Cheer up! You can write your daily routine in this channel: your team will be pleased when they come back on the project.', array_shift($participants));
        }

        $client = \JoliCode\Slack\ClientFactory::create($standupMeetingReminder->getSlackTeam()->getAccessToken());
        $client->chatPostMessage([
            'channel' => $standupMeetingReminder->getChannelId(),
            'text' => $message,
            'blocks' => json_encode([
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => $message,
                    ],
                ],
            ]),
        ]);
    }

    /**
     * @return array<string>|null
     */
    private function findParticipants(StandupMeetingReminder $standupMeetingReminder): ?array
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
            $assignments = array_values(array_filter($assignments, fn ($assignment): bool => $assignment->getStartDate()->format('Y-m-d') <= $today->format('Y-m-d') && $assignment->getEndDate()->format('Y-m-d') >= $today->format('Y-m-d')));
            $projects = $this->forecastDataSelector->getProjectsById(enabled: true);

            foreach ($assignments as $assignment) {
                $projectId = $assignment->getProjectId();

                if (
                    (0 === \count($standupMeetingReminder->getForecastClients()) || \in_array((string) $projects[$projectId]->getClientId(), $standupMeetingReminder->getForecastClients(), true))
                    && (0 === \count($standupMeetingReminder->getForecastProjects()) || \in_array((string) $projectId, $standupMeetingReminder->getForecastProjects(), true))
                ) {
                    if (null !== $assignment->getPersonId()) {
                        $members[$people[$assignment->getPersonId()]->getEmail()] = $memberName = sprintf(
                            '%s %s',
                            $people[$assignment->getPersonId()]->getFirstName(),
                            $people[$assignment->getPersonId()]->getLastName()
                        );
                    } elseif (null !== $assignment->getPlaceholderId()) {
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
        $slackUsers = $this->slackDataSelector->getUserIdsByEmail($standupMeetingReminder->getSlackTeam());
        ksort($members);

        foreach ($members as $email => $memberName) {
            if (isset($slackUsers[$email])) {
                $ping[] = sprintf('<@%s>', $slackUsers[$email]);
            } else {
                $ping[] = $memberName;
            }
        }

        return array_unique($ping);
    }
}
