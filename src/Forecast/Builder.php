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

use App\DataSelector\ForecastDataSelector;
use App\Entity\ForecastAccount;
use App\Entity\PublicForecast;

class Builder
{
    private $forecastDataSelector;

    public function __construct(ForecastDataSelector $forecastDataSelector)
    {
        $this->forecastDataSelector = $forecastDataSelector;
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
            if (!isset($weeks[$day['date']->format('W')])) {
                $weeks[$day['date']->format('W')] = 1;
            } else {
                ++$weeks[$day['date']->format('W')];
            }
        }

        foreach ($days as $day) {
            if (!isset($months[$day['date']->format('F')])) {
                $months[$day['date']->format('F')] = 1;
            } else {
                ++$months[$day['date']->format('F')];
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
        $userAssignments = [];

        if (\count($publicForecast->getClients()) > 0) {
            // filter by clients
            $allowedProjects = array_filter($allowedProjects, function ($project) use ($publicForecast) {
                return \in_array($project->getClientId(), $publicForecast->getClients(), true);
            });
        }

        if (\count($publicForecast->getProjects()) > 0) {
            // filter by projects
            $allowedProjects = array_filter($allowedProjects, function ($project) use ($publicForecast) {
                return \in_array($project->getId(), $publicForecast->getProjects(), true);
            });
        }

        foreach ($allowedProjects as $allowedProject) {
            $allowedProjectIds[] = $allowedProject->getId();
        }

        $assignments = array_filter($assignments, function ($assignment) use ($allowedProjectIds) {
            return \in_array($assignment->getProjectId(), $allowedProjectIds, true);
        });

        if (\count($publicForecast->getPeople()) + \count($publicForecast->getPlaceholders()) > 0) {
            // filter by people
            $assignments = array_filter($assignments, function ($assignment) use ($publicForecast) {
                return \in_array($assignment->getPersonId(), $publicForecast->getPeople(), true) || \in_array($assignment->getPlaceholderId(), $publicForecast->getPlaceholders(), true);
            });
        }

        foreach ($assignments as $assignment) {
            $project = $projects[$assignment->getProjectId()];
            $client = $clients[$project->getClientId()]->getName();
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

            if (null !== $assignment->getPersonId()) {
                $id = 'user_' . $assignment->getPersonId();
                $user = $users[$assignment->getPersonId()];
                $assignmentDays = $this->buildAssignmentInterval($assignment, $user->getWorkingDays());
            } else {
                $assignmentDays = $this->buildAssignmentInterval($assignment);
            }

            foreach ($assignmentDays as $assignmentDate) {
                $assignmentDay = $assignmentDate->format('Y-m-d');
                $assignmentWeek = $assignmentDate->format('W');

                if (\array_key_exists($assignmentDay, $days)) {
                    $userAssignments[$projectId]['users'][$id]['total'] += $duration;
                    $userAssignments[$projectId]['total_days'] += $duration;
                    $userAssignments[$projectId]['users'][$id]['days'][$assignmentDay] = $duration;

                    if (!isset($userAssignments[$projectId]['total'][$assignmentDay])) {
                        $userAssignments[$projectId]['total'][$assignmentDay] = 0;
                    }

                    $userAssignments[$projectId]['total'][$assignmentDay] += $duration;

                    if (!isset($userAssignments[$projectId]['weekly_total'][$assignmentWeek])) {
                        $userAssignments[$projectId]['weekly_total'][$assignmentWeek] = 0;
                    }

                    $userAssignments[$projectId]['weekly_total'][$assignmentWeek] += $duration;

                    if (!isset($userAssignments[$projectId]['firstDay']) || $userAssignments[$projectId]['firstDay'] < $assignmentDay) {
                        $userAssignments[$projectId]['firstDay'] = $assignmentDay;
                    }
                }
            }
        }

        foreach ($userAssignments as $projectId => $projectAssignments) {
            uasort($userAssignments[$projectId]['users'], function ($a, $b) {
                return $a['name'] > $b['name'];
            });
        }

        uasort($userAssignments, function ($a, $b) {
            return $a['firstDay'] > $b['firstDay'];
        });

        return $userAssignments;
    }

    private function buildAssignmentInterval($assignment, $weeklyDays = null)
    {
        $start = $assignment->getStartDate();
        $end = $assignment->getEndDate();

        if (null === $weeklyDays) {
            $workingDays = ['1', '2', '3', '4', '5'];
        } else {
            $workingDays = [];
            $weeklyDays->getMonday() && $workingDays[] = '1';
            $weeklyDays->getTuesday() && $workingDays[] = '2';
            $weeklyDays->getWednesday() && $workingDays[] = '3';
            $weeklyDays->getThursday() && $workingDays[] = '4';
            $weeklyDays->getFriday() && $workingDays[] = '5';
            $weeklyDays->getSaturday() && $workingDays[] = '6';
            $weeklyDays->getSunday() && $workingDays[] = '7';
        }

        $days = [];
        $current = $start;

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
