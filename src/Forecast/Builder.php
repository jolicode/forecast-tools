<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Forecast;

use App\Converter\PersonToWorkingDaysConverter;
use App\DataSelector\ForecastDataSelector;
use App\Entity\ForecastAccount;
use App\Entity\PublicForecast;
use JoliCode\Forecast\Api\Model\Person;
use function Symfony\Component\String\u;

class Builder
{
    protected PersonToWorkingDaysConverter $personToWorkingDaysConverter;
    private ForecastDataSelector $forecastDataSelector;

    public function __construct(ForecastDataSelector $forecastDataSelector, PersonToWorkingDaysConverter $personToWorkingDaysConverter)
    {
        $this->forecastDataSelector = $forecastDataSelector;
        $this->personToWorkingDaysConverter = $personToWorkingDaysConverter;
    }

    public function buildAssignments(PublicForecast $publicForecast, \DateTime $start, \DateTime $end)
    {
        $days = $this->buildPrettyDays($start, $end);
        $forecastDataSelector = $this->getForecastDataSelector($publicForecast->getForecastAccount());
        $assignments = $forecastDataSelector->getAssignments($start, $end);
        $clients = $forecastDataSelector->getClientsById();
        $projects = $forecastDataSelector->getProjectsById();
        $users = $forecastDataSelector->getPeopleById();
        $placeholders = $forecastDataSelector->getPlaceholdersById();

        return $this->buildPublicForecast($publicForecast, $days, $assignments, $clients, $projects, $users, $placeholders);
    }

    public function buildDays(\DateTime $start, \DateTime $end): array
    {
        $days = $this->buildPrettyDays($start, $end);
        $weeks = [];
        $months = [];

        foreach ($days as $day) {
            if (!isset($weeks[$day['date']->format('W Y')])) {
                $weeks[$day['date']->format('W Y')] = 1;
            } else {
                ++$weeks[$day['date']->format('W Y')];
            }
        }

        foreach ($days as $day) {
            if (!isset($months[$day['date']->format('F Y')])) {
                $months[$day['date']->format('F Y')] = 1;
            } else {
                ++$months[$day['date']->format('F Y')];
            }
        }

        return [$days, $weeks, $months];
    }

    private function getForecastDataSelector(ForecastAccount $forecastAccount): ForecastDataSelector
    {
        $this->forecastDataSelector->setForecastAccount($forecastAccount);

        return $this->forecastDataSelector;
    }

    private function buildPublicForecast($publicForecast, $days, $assignments, $clients, $projects, $users, $placeholders)
    {
        $allowedProjects = $projects;
        $allowedProjectIds = [];
        $userAssignments = [
            'total' => [
                'firstDay' => null,
                'users' => [],
                'total' => [],
                'total_days' => 0,
                'weekly_total' => [],
                'monthly_total' => [],
            ],
        ];

        if (\count($publicForecast->getClients()) > 0) {
            // filter by clients
            $allowedProjects = array_filter($allowedProjects, function ($project) use ($publicForecast): bool {
                return \in_array($project->getClientId(), $publicForecast->getClients(), true);
            });
        }

        if (\count($publicForecast->getProjects()) > 0) {
            // filter by projects
            $allowedProjects = array_filter($allowedProjects, function ($project) use ($publicForecast): bool {
                return \in_array($project->getId(), $publicForecast->getProjects(), true);
            });
        }

        foreach ($allowedProjects as $allowedProject) {
            $allowedProjectIds[] = $allowedProject->getId();
        }

        $assignments = array_filter($assignments, function ($assignment) use ($allowedProjectIds): bool {
            return \in_array($assignment->getProjectId(), $allowedProjectIds, true);
        });

        if (\count($publicForecast->getPeople()) + \count($publicForecast->getPlaceholders()) > 0) {
            // filter by people
            $assignments = array_filter($assignments, function ($assignment) use ($publicForecast): bool {
                return \in_array($assignment->getPersonId(), $publicForecast->getPeople(), true) || \in_array($assignment->getPlaceholderId(), $publicForecast->getPlaceholders(), true);
            });
        }

        foreach ($assignments as $assignment) {
            if (null === $assignment->getProjectId() || null === $assignment->getPersonId() && null === $assignment->getPlaceholderId() || null === $assignment->getAllocation()) {
                continue;
            }

            $project = $projects[$assignment->getProjectId()];
            $client = isset($clients[$project->getClientId()]) ? $clients[$project->getClientId()]->getName() : null;
            $projectId = $project->getId();
            $duration = $assignment->getAllocation() / 28800;

            if (!isset($userAssignments[$projectId])) {
                $userAssignments[$projectId] = [
                    'project' => $project,
                    'client' => $client,
                    'users' => [],
                    'total' => [],
                    'total_days' => 0,
                    'weekly_total' => [],
                    'monthly_total' => [],
                ];
            }

            if (null !== $assignment->getPersonId()) {
                $id = 'user_' . $assignment->getPersonId();
            } else {
                $id = 'placeholder_' . $assignment->getPlaceholderId();
            }

            if (!isset($userAssignments[$projectId]['users'][$id])) {
                if (null !== $assignment->getPersonId()) {
                    $user = $users[$assignment->getPersonId()];
                    $name = $user->getFirstName() . ' ' . $user->getLastName();
                } else {
                    $name = $placeholders[$assignment->getPlaceholderId()]->getName();
                }

                $userAssignments[$projectId]['users'][$id] = [
                    'name' => $name,
                    'days' => [],
                    'total' => 0,
                ];
            }

            if (!isset($userAssignments['total']['users'][$id])) {
                $userAssignments['total']['users'][$id] = [
                    /* @phpstan-ignore-next-line */
                    'name' => $name,
                    'days' => [],
                    'total' => 0,
                ];
            }

            if (null !== $assignment->getPersonId()) {
                $id = 'user_' . $assignment->getPersonId();
                $user = $users[$assignment->getPersonId()];
                $assignmentDays = $this->buildAssignmentInterval($assignment, $user);
            } else {
                $assignmentDays = $this->buildAssignmentInterval($assignment);
            }

            foreach ($assignmentDays as $assignmentDate) {
                $assignmentDay = $assignmentDate->format('Y-m-d');
                $assignmentWeek = $assignmentDate->format('W Y');
                $assignmentMonth = $assignmentDate->format('F Y');

                if (\array_key_exists($assignmentDay, $days)) {
                    $userAssignments[$projectId]['users'][$id]['total'] += $duration;
                    $userAssignments[$projectId]['total_days'] += $duration;
                    $userAssignments[$projectId]['users'][$id]['days'][$assignmentDay] = $duration;
                    $userAssignments['total']['users'][$id]['total'] += $duration;
                    $userAssignments['total']['total_days'] += $duration;
                    $userAssignments['total']['users'][$id]['days'][$assignmentDay] = $duration;

                    if (!isset($userAssignments[$projectId]['total'][$assignmentDay])) {
                        $userAssignments[$projectId]['total'][$assignmentDay] = 0;
                    }
                    if (!isset($userAssignments['total']['total'][$assignmentDay])) {
                        $userAssignments['total']['total'][$assignmentDay] = 0;
                    }

                    $userAssignments[$projectId]['total'][$assignmentDay] += $duration;
                    $userAssignments['total']['total'][$assignmentDay] += $duration;

                    // weekly_total
                    if (!isset($userAssignments[$projectId]['weekly_total'][$assignmentWeek])) {
                        $userAssignments[$projectId]['weekly_total'][$assignmentWeek] = 0;
                    }
                    if (!isset($userAssignments['total']['weekly_total'][$assignmentWeek])) {
                        $userAssignments['total']['weekly_total'][$assignmentWeek] = 0;
                    }

                    $userAssignments[$projectId]['weekly_total'][$assignmentWeek] += $duration;
                    $userAssignments['total']['weekly_total'][$assignmentWeek] += $duration;

                    // monthly_total
                    if (!isset($userAssignments[$projectId]['monthly_total'][$assignmentMonth])) {
                        $userAssignments[$projectId]['monthly_total'][$assignmentMonth] = 0;
                    }
                    if (!isset($userAssignments['total']['monthly_total'][$assignmentMonth])) {
                        $userAssignments['total']['monthly_total'][$assignmentMonth] = 0;
                    }

                    $userAssignments[$projectId]['monthly_total'][$assignmentMonth] += $duration;
                    $userAssignments['total']['monthly_total'][$assignmentMonth] += $duration;

                    if (!isset($userAssignments[$projectId]['firstDay']) || $userAssignments[$projectId]['firstDay'] > $assignmentDay) {
                        $userAssignments[$projectId]['firstDay'] = $assignmentDay;
                    }
                }
            }
        }

        foreach ($userAssignments as $projectId => $projectAssignments) {
            uasort($userAssignments[$projectId]['users'], function ($a, $b): int {
                return strcmp(u($a['name'])->folded(), u($b['name'])->folded());
            });
        }

        uasort($userAssignments, function ($a, $b): int {
            if ($a['firstDay'] === $b['firstDay']) {
                if (u($a['client'])->folded() === u($b['client'])->folded()) {
                    return strcmp(u($a['project']->getName())->folded(), u($b['project']->getName())->folded());
                }

                return strcmp(u($a['client'])->folded(), u($b['client'])->folded());
            }

            return strcmp($a['firstDay'], $b['firstDay']);
        });

        return $userAssignments;
    }

    private function buildAssignmentInterval($assignment, Person $user = null)
    {
        $current = $assignment->getStartDate();
        $end = $assignment->getEndDate();
        $workingDays = $this->personToWorkingDaysConverter->convert($user);
        $days = [];

        while ($current <= $end) {
            if (\in_array($current->format('N'), $workingDays, true)) {
                $days[] = clone $current;
            }

            $current->modify('+1 day');
        }

        return $days;
    }

    private function buildPrettyDays(\DateTime $start, \DateTime $end): array
    {
        if ($start >= $end) {
            throw new \DomainException('Please have the end date be after the start date');
        }

        $dates = [];
        $current = clone $start;

        while ($current <= $end) {
            $dates[$current->format('Y-m-d')] = [
                'date' => clone $current,
                'day' => $current->format('Y-m-d'),
                'isWeekend' => ($current->format('N') >= 6),
                'isFirstDayOfMonth' => ('1' === $current->format('j')),
                'isFirstDayOfWeek' => ('1' === $current->format('N')),
                'prettyDay' => $current->format('d/m'),
                'urlFormatted' => $current->format('Y/m/d'),
            ];
            $current->modify('+1 day');
        }

        return $dates;
    }
}
