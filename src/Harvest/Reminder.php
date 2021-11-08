<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Harvest;

use App\Client\HarvestClient;
use App\DataSelector\ForecastDataSelector;
use App\DataSelector\HarvestDataSelector;
use App\DataSelector\SlackDataSelector;
use App\Entity\HarvestAccount;
use App\Repository\HarvestAccountRepository;
use Bugsnag\Client;
use JoliCode\Forecast\Api\Model\Assignment;
use JoliCode\Harvest\Api\Model\TimeEntriesPostBody;
use JoliCode\Harvest\Api\Model\TimeEntry;
use JoliCode\Harvest\Api\Model\User as HarvestUser;

class Reminder
{
    public const BOT_NAME = 'Your personnal Harvest timesheet assistant';
    public const DAILY_EXPECTED_TOTAL = 8;
    public const MESSAGE_TEMPLATE_LAST_MONTH = '👋 Hello %s! It is time to fill your missing timesheets for the last month. Could you please check the missing information below?';
    public const MESSAGE_TEMPLATE_CURRENT_MONTH = '👋 Hello %s! Here is your timesheets report for the current month.';

    private ForecastDataSelector $forecastDataSelector;
    private HarvestAccountRepository $harvestAccountRepository;
    private HarvestClient $harvestClient;
    private HarvestDataSelector $harvestDataSelector;
    private SlackDataSelector $slackDataSelector;
    private Client $bugsnagClient;

    public function __construct(HarvestAccountRepository $harvestAccountRepository, HarvestClient $harvestClient, ForecastDataSelector $forecastDataSelector, HarvestDataSelector $harvestDataSelector, SlackDataSelector $slackDataSelector, Client $bugsnagClient)
    {
        $this->harvestAccountRepository = $harvestAccountRepository;
        $this->harvestClient = $harvestClient;
        $this->forecastDataSelector = $forecastDataSelector;
        $this->harvestDataSelector = $harvestDataSelector;
        $this->slackDataSelector = $slackDataSelector;
        $this->bugsnagClient = $bugsnagClient;
    }

    public function send()
    {
        $timesheetRemindersCount = 0;

        if (!$this->mustSend()) {
            return $timesheetRemindersCount;
        }

        $firstDayOfLastMonth = new \DateTime('first day of last month');
        $lastDayOfLastMonth = new \DateTime('last day of last month');
        $harvestAccounts = $this->harvestAccountRepository->findAllHavingTimesheetReminderSlackTeam();

        foreach ($harvestAccounts as $harvestAccount) {
            try {
                $missingProjectAssignmentsIssues = $this->buildMissingItemsSlackBlocks(
                    $this->buildMissingProjectAssignmentsIssues($harvestAccount, $firstDayOfLastMonth, $lastDayOfLastMonth),
                    $harvestAccount
                );
                $issues = $this->buildSlackBlocks(
                    $this->buildIssues($harvestAccount, $firstDayOfLastMonth, $lastDayOfLastMonth)
                );

                if (\count($issues) + \count($missingProjectAssignmentsIssues) > 0) {
                    $slackClient = \JoliCode\Slack\ClientFactory::create(
                        $harvestAccount->getTimesheetReminderSlackTeam()->getSlackTeam()->getAccessToken()
                    );

                    if (\count($missingProjectAssignmentsIssues) > 0) {
                        $adminUsers = $this->getHarvestAdminSlackIds($harvestAccount);

                        foreach ($adminUsers as $adminUser) {
                            $payload = [
                                'channel' => $adminUser,
                                'username' => self::BOT_NAME,
                                'text' => $missingProjectAssignmentsIssues[0]['text']['text'],
                                'blocks' => json_encode($missingProjectAssignmentsIssues),
                            ];

                            try {
                                $slackClient->chatPostMessage($payload);
                            } catch (\Exception $e) {
                                $this->bugsnagClient->notifyException($e, function ($report) use ($harvestAccount, $firstDayOfLastMonth, $lastDayOfLastMonth, $payload): void {
                                    $report->setMetaData([
                                        'harvestAccount' => [
                                            'id' => $harvestAccount->getId(),
                                            'name' => $harvestAccount->getName(),
                                        ],
                                        'firstDayOfLastMonth' => $firstDayOfLastMonth,
                                        'lastDayOfLastMonth' => $lastDayOfLastMonth,
                                        'payload' => $payload,
                                    ]);
                                });
                            }
                        }
                    }

                    foreach ($issues as $issue) {
                        // send a Slack notification to this user
                        $payload = [
                            'channel' => $issue['slackUser']->getId(),
                            'username' => self::BOT_NAME,
                            'text' => $issue['message'],
                            'blocks' => json_encode($issue['blocks']),
                        ];

                        try {
                            $slackClient->chatPostMessage($payload);
                        } catch (\Exception $e) {
                            $this->bugsnagClient->notifyException($e, function ($report) use ($harvestAccount, $firstDayOfLastMonth, $lastDayOfLastMonth, $payload): void {
                                $report->setMetaData([
                                    'harvestAccount' => [
                                        'id' => $harvestAccount->getId(),
                                        'name' => $harvestAccount->getName(),
                                    ],
                                    'firstDayOfLastMonth' => $firstDayOfLastMonth,
                                    'lastDayOfLastMonth' => $lastDayOfLastMonth,
                                    'payload' => $payload,
                                ]);
                            });
                        }

                        ++$timesheetRemindersCount;
                    }
                }
            } catch (\Exception $e) {
                $this->bugsnagClient->notifyException($e, function ($report) use ($harvestAccount, $firstDayOfLastMonth, $lastDayOfLastMonth): void {
                    $report->setMetaData([
                        'harvestAccount' => [
                            'id' => $harvestAccount->getId(),
                            'name' => $harvestAccount->getName(),
                        ],
                        'firstDayOfLastMonth' => $firstDayOfLastMonth,
                        'lastDayOfLastMonth' => $lastDayOfLastMonth,
                    ]);
                });
            }
        }

        return $timesheetRemindersCount;
    }

    public function buildForHarvestAccount(HarvestAccount $harvestAccount, $currentMonth = false): array
    {
        return $this->buildForHarvestAccountAndUser($harvestAccount, null, $currentMonth);
    }

    public function buildForHarvestAccountAndUser(HarvestAccount $harvestAccount, HarvestUser $harvestUser = null, $currentMonth = false): array
    {
        if ($currentMonth) {
            $firstDayOfLastMonth = new \DateTime('first day of this month');
            $lastDayOfLastMonth = new \DateTime('last day of this month');
        } else {
            $firstDayOfLastMonth = new \DateTime('first day of last month');
            $lastDayOfLastMonth = new \DateTime('last day of last month');
        }

        return $this->buildSlackBlocks(
            $this->buildIssues($harvestAccount, $firstDayOfLastMonth, $lastDayOfLastMonth, $harvestUser),
            $currentMonth
        );
    }

    public function copy(HarvestAccount $harvestAccount, HarvestUser $harvestUser, string $week)
    {
        $firstDayOfWeek = new \DateTime($week);
        $lastDayOfWeek = (new \DateTime($week))->modify('next sunday');
        $issues = $this->buildIssues($harvestAccount, $firstDayOfWeek, $lastDayOfWeek, $harvestUser);
        $forecastProjects = $this->forecastDataSelector->getProjectsById();

        foreach ($issues[$harvestUser->getId()]['weeks'][$week]['days'] as $day => $dailyIssue) {
            $remove = $dailyIssue['remove'];
            $add = $dailyIssue['add'];

            foreach ($remove as $harvestTimeEntry) {
                $this->harvestClient->__client()->deleteTimeEntry($harvestTimeEntry->getId());
            }

            foreach ($add as $assignment) {
                $harvestProjectId = $forecastProjects[$assignment->getProjectId()]->getHarvestId();
                $harvestTasks = $this->harvestDataSelector->getTaskAssignmentsForProjectId($harvestProjectId);
                $harvestTaskAssignment = array_shift($harvestTasks);

                $harvestBody = new TimeEntriesPostBody();
                $harvestBody->setHours($assignment->getAllocation() / 3600);
                $harvestBody->setNotes($assignment->getNotes());
                $harvestBody->setProjectId($harvestProjectId);
                $harvestBody->setSpentDate(new \DateTime($day));
                $harvestBody->setTaskId($harvestTaskAssignment->getTask()->getId());
                $harvestBody->setUserId($harvestUser->getId());
                $this->harvestClient->__client()->createTimeEntry($harvestBody);
            }
        }
    }

    private function buildHoursDiffSuffix($hoursDiff, $addRedCross = false)
    {
        if (0.0 !== (float) $hoursDiff) {
            return sprintf(', %sh %s%s', abs($hoursDiff), $hoursDiff < 0 ? 'too many' : 'missing', $addRedCross ? ' ❌' : '');
        }

        return '';
    }

    private function buildIssues(HarvestAccount $harvestAccount, \DateTime $firstDayOfLastMonth, \DateTime $lastDayOfLastMonth, HarvestUser $harvestUser = null): array
    {
        $issues = [];
        $slackTeam = $harvestAccount->getTimesheetReminderSlackTeam();

        if (null === $slackTeam) {
            return [];
        }

        $slackUsers = $this->slackDataSelector->getUsersByEmail($slackTeam->getSlackTeam());
        $this->harvestDataSelector->setHarvestAccount($harvestAccount);
        $this->forecastDataSelector->setForecastAccount($harvestAccount->getForecastAccount());
        $forecastPeople = $this->forecastDataSelector->getPeopleById();
        $forecastProjects = $this->forecastDataSelector->getProjectsById();
        $days = $this->buildDays($firstDayOfLastMonth, $lastDayOfLastMonth);

        if (null === $harvestUser) {
            $users = $this->harvestDataSelector->getEnabledUsers();
            $userTimesheets = $this->harvestDataSelector
                ->disableCacheForNextRequestOnly()
                ->getUserTimeEntries($firstDayOfLastMonth, $lastDayOfLastMonth);
            $forecastAssignments = $this->forecastDataSelector
                ->disableCacheForNextRequestOnly()
                ->getAssignments($firstDayOfLastMonth, $lastDayOfLastMonth);

            foreach ($users as $user) {
                if (!\in_array($user->getId(), $harvestAccount->getDoNotSendTimesheetReminderFor(), true) && isset($slackUsers[$user->getEmail()])) {
                    $issues[$user->getId()] = [
                        'user' => $user,
                        'weeks' => [],
                        'isValid' => true,
                        'slackUser' => $slackUsers[$user->getEmail()],
                    ];
                }
            }
        } else {
            $forecastPeopleByHarvestId = $this->forecastDataSelector->getPeopleById('getHarvestUserId');
            $forecastPersonId = $forecastPeopleByHarvestId[$harvestUser->getId()]->getId();
            $userTimesheets = $this->harvestDataSelector
                ->disableCacheForNextRequestOnly()
                ->getUserTimeEntries($firstDayOfLastMonth, $lastDayOfLastMonth, ['user_id' => $harvestUser->getId()]);
            $forecastAssignments = $this->forecastDataSelector
                ->disableCacheForNextRequestOnly()
                ->getAssignments($firstDayOfLastMonth, $lastDayOfLastMonth, ['person_id' => $forecastPersonId]);
            $issues[$harvestUser->getId()] = [
                'user' => $harvestUser,
                'weeks' => [],
                'isValid' => true,
                'slackUser' => $slackUsers[$harvestUser->getEmail()],
            ];
        }

        foreach ($issues as $harvestUserId => $issue) {
            $timeEntries = $userTimesheets[$harvestUserId] ?? [];
            $dailyHoursCapacity = $issue['user']->getWeeklyCapacity() / 5 / 3600;

            foreach ($days as $day => $dayProperties) {
                if ($dayProperties['isWeekend']) {
                    continue;
                }

                $firstDayOfWeek = $dayProperties['firstDayOfWeek'];
                $week = $dayProperties['firstDayOfWeekInRangeAsString'];

                if (!isset($issues[$harvestUserId]['weeks'][$week])) {
                    $issues[$harvestUserId]['weeks'][$week] = [
                        'days' => [],
                        'total_hours_declared' => 0,
                        'total_hours_expected' => 0,
                        'link' => sprintf(
                            '%s/time/week/%s/%s/%s/%s',
                            $harvestAccount->getBaseUri(),
                            $firstDayOfWeek->format('Y'),
                            $firstDayOfWeek->format('m'),
                            $firstDayOfWeek->format('d'),
                            $harvestUserId
                        ),
                        'name' => $firstDayOfWeek->format('l, F jS'),
                    ];
                }

                $dailyTimeEntries = $timeEntries['entries'][$day] ?? [];
                $dailyAssignments = array_filter($forecastAssignments, function ($assignment) use ($forecastPeople, $harvestUserId, $day): bool {
                    return
                        isset($forecastPeople[$assignment->getPersonId()])
                        && $forecastPeople[$assignment->getPersonId()]->getHarvestUserId() === $harvestUserId
                        && $assignment->getStartDate()->format('Y-m-d') <= $day
                        && $assignment->getEndDate()->format('Y-m-d') >= $day;
                });
                $onlyInForecast = $this->findOnlyInForecast($dailyAssignments, $dailyTimeEntries, $forecastProjects);
                $onlyInHarvest = $this->findOnlyInHarvest($dailyAssignments, $dailyTimeEntries, $forecastProjects);
                $duplicateTimeEntries = $this->findDuplicateTimeEntries($dailyTimeEntries);
                $inBoth = array_filter($this->findInBoth($dailyAssignments, $dailyTimeEntries, $forecastProjects), function (TimeEntry $timeEntry) use ($duplicateTimeEntries): bool {
                    return !\in_array($timeEntry, $duplicateTimeEntries, true);
                });
                $hours = array_reduce($dailyTimeEntries, function ($carry, TimeEntry $item) {
                    return $carry + $item->getHours();
                }, 0);
                $issues[$harvestUserId]['weeks'][$week]['days'][$day] = [
                    'add' => $onlyInForecast,
                    'remove' => array_merge($onlyInHarvest, $duplicateTimeEntries),
                    'keep' => $inBoth,
                    'day' => $days[$day]['pretty'],
                    'hours_declared' => $hours,
                    'hours_expected' => $dailyHoursCapacity,
                ];
                $issues[$harvestUserId]['weeks'][$week]['total_hours_expected'] += $dailyHoursCapacity;
                $issues[$harvestUserId]['weeks'][$week]['total_hours_declared'] += $hours;
                $issues[$harvestUserId]['isValid'] = $issues[$harvestUserId]['isValid']
                    && (0 === \count($onlyInForecast) + \count($onlyInHarvest))
                    && (float) $hours === (float) $dailyHoursCapacity
                ;
            }
        }

        return $issues;
    }

    private function buildMissingProjectAssignmentsIssues(HarvestAccount $harvestAccount, \DateTime $firstDayOfLastMonth, \DateTime $lastDayOfLastMonth, HarvestUser $harvestUser = null): array
    {
        $issues = [
            'no_harvest_project' => [],
            'missing_harvest_user_assignment' => [],
        ];
        $slackTeam = $harvestAccount->getTimesheetReminderSlackTeam();

        if (null === $slackTeam) {
            return [];
        }

        $this->harvestDataSelector->setHarvestAccount($harvestAccount);
        $this->forecastDataSelector->setForecastAccount($harvestAccount->getForecastAccount());
        $forecastPeople = $this->forecastDataSelector->getPeopleById();
        $forecastProjects = $this->forecastDataSelector->getProjectsById();
        $harvestUserAssignments = $this->harvestDataSelector->getUserAssignments(['is_active' => true]);
        $forecastAssignments = $this->forecastDataSelector->getAssignments($firstDayOfLastMonth, $lastDayOfLastMonth);

        foreach ($forecastAssignments as $forecastAssignment) {
            $forecastProject = $forecastProjects[$forecastAssignment->getProjectId()] ?? null;
            $forecastPerson = $forecastPeople[$forecastAssignment->getPersonId()] ?? null;

            if (null === $forecastProject || null === $forecastPerson || null === $forecastPerson->getHarvestUserId()) {
                continue;
            }

            if (null === $forecastProject->getHarvestId()) {
                $issues['no_harvest_project'][$forecastProject->getId()] = $forecastProject;
            } elseif (!isset($harvestUserAssignments[$forecastPerson->getHarvestUserId()])
                || !isset($harvestUserAssignments[$forecastPerson->getHarvestUserId()][$forecastProject->getHarvestId()])) {
                if (!isset($issues['missing_harvest_user_assignment'][$forecastPerson->getHarvestUserId()])) {
                    $issues['missing_harvest_user_assignment'][$forecastPerson->getHarvestUserId()] = [
                        'forecast_user' => $forecastPerson,
                        'projects' => [],
                    ];
                }

                $issues['missing_harvest_user_assignment'][$forecastPerson->getHarvestUserId()]['projects'][$forecastProject->getId()] = [
                    'project' => $forecastProject,
                    'assignment' => $forecastAssignment,
                ];
            }
        }

        return $issues;
    }

    private function buildMissingItemsSlackBlocks(array $issues, HarvestAccount $harvestAccount): array
    {
        $blocks = [];

        if (\count($issues['no_harvest_project']) + \count($issues['missing_harvest_user_assignment']) > 0) {
            $blocks = [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => 'There are some issues with in the Harvest projects configuration and assignments, that could prevent some of your users from filling their timesheets for last month.',
                    ],
                ], [
                    'type' => 'divider',
                ],
            ];
        }

        if (\count($issues['no_harvest_project']) > 0) {
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => 'Projects that are used in Forecast and are not linked to a Harvest project',
                ],
            ];

            foreach ($issues['no_harvest_project'] as $forecastProject) {
                $blocks[] = [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => sprintf('[%s] %s', $forecastProject->getCode(), $forecastProject->getName()),
                    ],
                ];
            }

            $blocks[] = [
                'type' => 'divider',
            ];
        }

        if (\count($issues['missing_harvest_user_assignment']) > 0) {
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => 'The following users are not assigned projects in Harvest, while they are in Forecast. They won\'t be able to fill their harvest timesheets:',
                ],
            ];

            foreach ($issues['missing_harvest_user_assignment'] as $userIssues) {
                $blocks[] = [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => sprintf('*%s %s*', $userIssues['forecast_user']->getFirstName(), $userIssues['forecast_user']->getLastName()),
                    ],
                ];

                foreach ($userIssues['projects'] as $project) {
                    $blocks[] = [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => sprintf(
                                '<%s/projects/%s|[%s] %s>',
                                $harvestAccount->getBaseUri(),
                                $project['project']->getHarvestId(),
                                $project['project']->getCode(),
                                $project['project']->getName()
                            ),
                        ],
                    ];
                }
            }

            $blocks[] = [
                'type' => 'divider',
            ];
        }

        if (\count($blocks) > 50) {
            $remainingIssuesCount = \count($blocks) - 49;
            $blocks = \array_slice($blocks, 0, 49);
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => sprintf('There are %s such other issues that are not displayed', $remainingIssuesCount),
                ],
            ];
        }

        return $blocks;
    }

    private function buildSlackBlocks(array $issues, $currentMonth = false): array
    {
        $forecastProjects = $this->forecastDataSelector->getProjectsById();

        foreach ($issues as $userId => $issue) {
            if ($issue['isValid']) {
                unset($issues[$userId]);
                continue;
            }

            $mainMessage = sprintf($currentMonth ? self::MESSAGE_TEMPLATE_CURRENT_MONTH : self::MESSAGE_TEMPLATE_LAST_MONTH, $issue['user']->getFirstName());
            $issues[$userId]['message'] = $mainMessage;
            $blocks = [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => $mainMessage,
                    ],
                ], [
                    'type' => 'divider',
                ],
            ];

            foreach ($issue['weeks'] as $week => $weeklyIssue) {
                $weeklyHoursDiff = $weeklyIssue['total_hours_expected'] - $weeklyIssue['total_hours_declared'];
                $accessory = null;
                $detailsMessages = [];

                foreach ($weeklyIssue['days'] as $day) {
                    if ((0.0 !== (float) $weeklyHoursDiff) || (\count($day['add']) + \count($day['remove']) > 0)) {
                        $detailsMessages[] = sprintf(
                            '%s *%s*%s',
                            $this->getClockEmoji($day['hours_declared']),
                            $day['day'],
                            $this->buildHoursDiffSuffix(
                                $day['hours_expected'] - $day['hours_declared'],
                                0 === \count($day['add']) + \count($day['remove'])
                            )
                        );

                        foreach ($day['remove'] as $harvestTimeEntry) {
                            $detailsMessages[] = sprintf('󠀠　 ➖ _%s_ (%sh)', $harvestTimeEntry->getProject()->getName(), $harvestTimeEntry->getHours());
                        }

                        foreach ($day['keep'] as $harvestTimeEntry) {
                            $detailsMessages[] = sprintf('󠀠　 🆗 _%s_ (%sh)', $harvestTimeEntry->getProject()->getName(), $harvestTimeEntry->getHours());
                        }

                        foreach ($day['add'] as $forecastAssignment) {
                            $detailsMessages[] = sprintf('󠀠　 ➕ _%s_ (%sh)', $forecastProjects[$forecastAssignment->getProjectId()]->getName(), $forecastAssignment->getAllocation() / 3600);
                        }

                        if (\count($day['add']) + \count($day['remove']) > 0) {
                            $accessory = [
                                'type' => 'button',
                                'text' => [
                                    'type' => 'plain_text',
                                    'text' => 'Apply changes in Harvest',
                                    'emoji' => true,
                                ],
                                'value' => $week,
                                'action_id' => Handler::ACTION_PREFIX . '.' . Handler::ACTION_COPY,
                            ];
                        }
                    }
                }

                if (\count($detailsMessages) > 0) {
                    $message = sprintf(
                        '📅 *<%s|Week starting on %s>*%s',
                        $weeklyIssue['link'],
                        $weeklyIssue['name'],
                        $this->buildHoursDiffSuffix($weeklyHoursDiff)
                    );
                    $block = [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => $message . "\n" . implode("\n", $detailsMessages) . "\n",
                        ],
                    ];

                    if (null !== $accessory) {
                        $block['accessory'] = $accessory;
                    }

                    $blocks[] = $block;
                }
            }

            $blocks = array_merge($blocks, [
                [
                    'type' => 'divider',
                ], [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => 'Please also take care to check that the filled timesheets are correct 😇',
                    ],
                ], [
                    'type' => 'actions',
                    'elements' => [[
                        'type' => 'button',
                        'text' => [
                            'type' => 'plain_text',
                            'text' => 'reload',
                            'emoji' => true,
                        ],
                        'value' => $currentMonth ? Handler::SLACK_COMMAND_OPTION_CURRENT : 'previous',
                        'action_id' => Handler::ACTION_PREFIX . '.' . Handler::ACTION_RELOAD,
                    ]],
                ],
            ]);

            $issues[$userId]['blocks'] = $blocks;
        }

        return $issues;
    }

    private function buildDays(\DateTime $start, \DateTime $end): array
    {
        if ($start >= $end) {
            throw new \DomainException('Please have the end date be after the start date');
        }

        $days = [];
        $current = clone $start;

        while ($current <= $end) {
            $firstDayOfWeek = $this->getFirstDayOfWeek($current);
            $firstDayOfWeekInRangeAsString = max($firstDayOfWeek, $start)->format('Y-m-d');
            $days[$current->format('Y-m-d')] = [
                'date' => clone $current,
                'firstDayOfWeek' => $firstDayOfWeek,
                'firstDayOfWeekInRangeAsString' => $firstDayOfWeekInRangeAsString,
                'isWeekend' => ($current->format('N') >= 6),
                'pretty' => $current->format('F jS'),
            ];
            $current->modify('+1 day');
        }

        return $days;
    }

    private function findDuplicateTimeEntries(array $timeEntries): array
    {
        if (\count($timeEntries) < 2) {
            return [];
        }

        $duplicates = $timeEntries;
        usort($timeEntries, function ($a, $b) {
            return ($b->getUpdatedAt() > $a->getUpdatedAt()) ? 1 : -1;
        });

        foreach ($timeEntries as $i => $timeEntry) {
            foreach ($timeEntries as $j => $value) {
                if ($i !== $j
                    && $value->getProject()->getId() === $timeEntry->getProject()->getId()
                    && $value->getTask()->getId() === $timeEntry->getTask()->getId()
                    && $value->getHours() === $timeEntry->getHours()
                ) {
                    unset($timeEntries[$i]);
                    break;
                }
            }
        }

        return array_diff_key($duplicates, $timeEntries);
    }

    private function findInBoth(array $assignments, array $timeEntries, array $forecastProjects): array
    {
        return array_filter($timeEntries, function (TimeEntry $timeEntry) use ($assignments, $forecastProjects): bool {
            $matchingAssignments = array_filter($assignments, function (Assignment $assignment) use ($timeEntry, $forecastProjects): bool {
                return $this->isEquivalentEntries($assignment, $timeEntry, $forecastProjects);
            });

            return 1 === \count($matchingAssignments);
        });
    }

    private function findOnlyInForecast(array $assignments, array $timeEntries, array $forecastProjects): array
    {
        return array_filter($assignments, function (Assignment $assignment) use ($timeEntries, $forecastProjects): bool {
            $matchingTimeEntries = array_filter($timeEntries, function (TimeEntry $timeEntry) use ($assignment, $forecastProjects): bool {
                return $this->isEquivalentEntries($assignment, $timeEntry, $forecastProjects);
            });

            return 0 === \count($matchingTimeEntries);
        });
    }

    private function findOnlyInHarvest(array $assignments, array $timeEntries, array $forecastProjects): array
    {
        return array_filter($timeEntries, function (TimeEntry $timeEntry) use ($assignments, $forecastProjects): bool {
            $matchingAssignments = array_filter($assignments, function (Assignment $assignment) use ($timeEntry, $forecastProjects): bool {
                return $this->isEquivalentEntries($assignment, $timeEntry, $forecastProjects);
            });

            return 0 === \count($matchingAssignments);
        });
    }

    private function getHarvestAdminSlackIds(HarvestAccount $harvestAccount)
    {
        $ids = [];
        $slackTeam = $harvestAccount->getTimesheetReminderSlackTeam();

        if (null === $slackTeam) {
            return $ids;
        }

        $slackUsers = $this->slackDataSelector->getUsersByEmail($slackTeam->getSlackTeam());
        $this->harvestDataSelector->setHarvestAccount($harvestAccount);
        $users = $this->harvestDataSelector->getEnabledUsers();

        foreach ($users as $user) {
            if ($user->getIsAdmin() && isset($slackUsers[$user->getEmail()])) {
                $ids[] = $slackUsers[$user->getEmail()]->getId();
            }
        }

        return $ids;
    }

    private function getClockEmoji($value)
    {
        if ($value < 1 || $value > 12) {
            $value = 12;
        }

        return \IntlChar::chr(128336 + ($value - 1) % 12);
    }

    private function getFirstDayOfWeek(\DateTime $date): \DateTime
    {
        $firstDayOfWeek = clone $date;

        if ('1' !== $firstDayOfWeek->format('N')) {
            $firstDayOfWeek->modify('last monday');
        }

        return $firstDayOfWeek;
    }

    /**
     * @param \JoliCode\Forecast\Api\Model\Project[] $forecastProjects
     *
     * @return bool Whether or not the assignment and the entries are related
     */
    private function isEquivalentEntries(Assignment $assignment, TimeEntry $timeEntry, array $forecastProjects): bool
    {
        return $this->isSameProject($assignment, $timeEntry, $forecastProjects)
            && (float) $timeEntry->getHours() === (float) $assignment->getAllocation() / 3600;
    }

    /**
     * @param \JoliCode\Forecast\Api\Model\Project[] $forecastProjects
     *
     * @return bool Whether or not the assignment and the entry are about the same project
     */
    private function isSameProject(Assignment $assignment, TimeEntry $timeEntry, array $forecastProjects): bool
    {
        return $timeEntry->getProject()->getId() === $forecastProjects[$assignment->getProjectId()]->getHarvestId();
    }

    private function mustSend()
    {
        $today = new \DateTime();
        $dayOfMonth = (int) $today->format('j');
        $dayOfWeek = (int) $today->format('N');

        return
            // the first day of the month is a working day
            (1 === $dayOfMonth && $dayOfWeek < 6)

            // the 2nd or 3rd day of the month is also the first day of a week
            || ($dayOfMonth <= 3 && 1 === $dayOfWeek)
        ;
    }
}
