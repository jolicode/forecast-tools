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

use App\Entity\PublicForecast;

class Builder
{
    public function buildAssignments(PublicForecast $publicForecast, \DateTime $start, \DateTime $end)
    {
        $days = $this->buildPrettyDays($start, $end);
        $account = $publicForecast->getForecastAccount();
        $client = \JoliCode\Forecast\ClientFactory::create(
            $account->getAccessToken(),
            $account->getForecastId()
        );

        $options = [
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
        ];

        $assignments = $client->listAssignments($options)->getAssignments();
        $clients = self::makeLookup($client->listClients()->getClients());
        $projects = self::makeLookup($client->listProjects()->getProjects());
        $users = self::makeLookup($client->listPeople()->getPeople());
        $placeholders = self::makeLookup($client->listPlaceholders()->getPlaceholders());

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

        foreach ($assignments as $assignment) {
            $project = $projects[$assignment->getProjectId()];
            $client = $clients[$project->getClientId()]->getName();
            $projectId = $project->getId();

            if (!isset($userAssignments[$projectId])) {
                $userAssignments[$projectId] = [
                    'project' => $project,
                    'client' => $client,
                    'users' => [],
                    'total' => [],
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

                if (array_key_exists($assignmentDay, $days)) {
                    $userAssignments[$projectId]['users'][$id]['days'][$assignmentDay] = $assignment->getAllocation() / 28800;

                    if (!isset($userAssignments[$projectId]['total'][$assignmentDay])) {
                        $userAssignments[$projectId]['total'][$assignmentDay] = 0;
                    }

                    $userAssignments[$projectId]['total'][$assignmentDay] += $assignment->getAllocation() / 28800;

                    if (!isset($userAssignments[$projectId]['weekly_total'][$assignmentWeek])) {
                        $userAssignments[$projectId]['weekly_total'][$assignmentWeek] = 0;
                    }

                    $userAssignments[$projectId]['weekly_total'][$assignmentWeek] += $assignment->getAllocation() / 28800;

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
        $start = new \DateTime($assignment->getStartDate());
        $end = new \DateTime($assignment->getEndDate());

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

    private static function makeLookup($struct, $methodName = 'getId')
    {
        $lookup = [];

        foreach ($struct as $data) {
            $lookup[$data->$methodName()] = $data;
        }

        return $lookup;
    }
}
