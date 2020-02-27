<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\ForecastReminder;

use App\Entity\ForecastReminder;

class Builder
{
    protected $forecastReminder;
    protected $client;
    protected $clientOverrides;
    protected $projectOverrides;

    public function __construct(ForecastReminder $forecastReminder)
    {
        $this->forecastReminder = $forecastReminder;
        $this->clientOverrides = self::makeLookup($forecastReminder->getClientOverrides(), 'getClientId');
        $this->projectOverrides = self::makeLookup($forecastReminder->getProjectOverrides(), 'getProjectId');
        $account = $forecastReminder->getForecastAccount();
        $this->client = \JoliCode\Forecast\ClientFactory::create(
            $account->getAccessToken(),
            $account->getForecastId()
        );
    }

    public function buildMessage(\DateTime $start = null)
    {
        $report = [];
        $result = [];

        if (null === $start) {
            $start = new \DateTime('+1 day');
        }

        $end = clone $start;
        $end->modify('+2 months');

        $options = [
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
        ];
        $this->fetchData($options);
        $longuestNameLength = 0;

        foreach ($this->users as $user) {
            if (\count($this->forecastReminder->getOnlyUsers()) > 0 && !\in_array($user->getId(), $this->forecastReminder->getOnlyUsers(), true)) {
                continue;
            }

            if (\count($this->forecastReminder->getExceptUsers()) > 0 && \in_array($user->getId(), $this->forecastReminder->getExceptUsers(), true)) {
                continue;
            }

            $name = $user->getFirstName() . ' ' . $user->getLastName();
            $activities = $this->getActivity($user, $start);

            if (0 === \count($activities)) {
                $activitiesAsText = $this->forecastReminder->getDefaultActivityName() ?: 'not set';
            } elseif (1 === \count($activities) && $this->isTimeOffActivity($activities[0])) {
                $endDate = $this->getTimeOffEndDate($user);
                $timeOffActivityName = $this->forecastReminder->getTimeOffActivityName() ?: 'holidays (until %s)';
                $activitiesAsText = sprintf($timeOffActivityName, $endDate->format('Y-m-d'));
            } else {
                $activitiesAsText = $this->getActivitiesAsText($activities);
            }

            $report[$name] = $activitiesAsText;

            if (mb_strlen($name) > $longuestNameLength) {
                $longuestNameLength = mb_strlen($name);
            }
        }

        foreach ($report as $name => $activities) {
            if (mb_strlen($name) < $longuestNameLength) {
                $name = $name . str_repeat(' ', $longuestNameLength - mb_strlen($name));
            }

            $result[] = sprintf('` %s ` %s', $name, $activities);
        }

        return implode("\n", $result);
    }

    public function buildTitle(\DateTime $startDate = null)
    {
        if (null === $startDate) {
            $startDate = new \DateTime('+1 day');
        }

        return sprintf(
            '%s :sunrise: for <https://forecastapp.com/%s/schedule/team|%s>',
            $startDate->format('Y-m-d'),
            $this->forecastReminder->getForecastAccount()->getForecastId(),
            $this->forecastReminder->getForecastAccount()->getName()
        );
    }

    public static function makeLookup($struct, $methodName = 'getId')
    {
        $lookup = [];

        foreach ($struct as $data) {
            $lookup[$data->$methodName()] = $data;
        }

        return $lookup;
    }

    private function fetchData($options)
    {
        $users = $this->client->listPeople()->getPeople();
        $users = array_values(array_filter($users, function ($user) {
            return false === $user->getArchived();
        }));
        usort($users, function ($a, $b) {
            if ($a->getFirstName() === $b->getFirstName()) {
                return $a->getLastName() > $b->getLastName();
            }

            return $a->getFirstName() > $b->getFirstName();
        });

        $this->assignments = $this->client->listAssignments($options)->getAssignments();
        $this->clients = self::makeLookup($this->client->listClients()->getClients());
        $this->projects = self::makeLookup($this->client->listProjects()->getProjects());
        $this->users = $users;
    }

    private function getActivitiesAsText($activities)
    {
        $activities = array_map(function ($activity) {
            if (isset($this->projectOverrides[$activity->getProjectId()])) {
                return $this->projectOverrides[$activity->getProjectId()];
            }

            $project = $this->projects[$activity->getProjectId()];

            if (isset($this->clientOverrides[$project->getClientId()])) {
                return $this->clientOverrides[$project->getClientId()];
            }

            $client = $this->clients[$project->getClientId()];

            return $client->getName() . ' | ' . $project->getName();
        }, $activities);

        if (\count($activities) > 1) {
            return implode('', [
                implode(', ', \array_slice($activities, 0, -1)),
                ' and ',
                \array_slice($activities, -1)[0],
            ]);
        }

        return $activities[0];
    }

    private function getActivity($user, \DateTime $date)
    {
        $workingDays = $this->getWorkingDays($user);

        if (!\in_array($date->format('N'), $workingDays, true)) {
            return [];
        }

        $activities = $this->getPersonActivities($user);

        return array_values(array_filter($activities, function ($activity) use ($date) {
            return $activity->getStartDate()->format('Y-m-d') <= $date->format('Y-m-d') && $activity->getEndDate()->format('Y-m-d') >= $date->format('Y-m-d');
        }));
    }

    private function getPersonActivities($user)
    {
        return array_values(array_filter($this->assignments, function ($activity) use ($user) {
            return $activity->getPersonId() === $user->getId();
        }));
    }

    private function getTimeOffEndDate($user)
    {
        $activities = $this->getPersonActivities($user);
        $activities = array_values(array_filter($activities, function ($activity) {
            return $this->isTimeOffActivity($activity);
        }));
        $activities = array_map(function ($activity) {
            $endDate = clone $activity->getEndDate();

            if ($endDate->format('N') >= 5) {
                $endDate->modify('next monday');
                $activity->setEndDate($endDate);
            }

            return $activity;
        }, $activities);
        usort($activities, function ($a, $b) {
            return $a->getEndDate() < $b->getEndDate();
        });
        $i = 1;
        $activity = $activities[0];

        while ($i < \count($activities)) {
            $currentActivity = $activities[$i];

            if ($currentActivity->getStartDate() > $activity->getEndDate()) {
                break;
            }

            $activity = $currentActivity;
            ++$i;
        }

        return $activity->getEndDate();
    }

    private function getWorkingDays($user)
    {
        $workingDays = [];
        $weeklyDays = $user->getWorkingDays();

        $weeklyDays->getMonday() && $workingDays[] = '1';
        $weeklyDays->getTuesday() && $workingDays[] = '2';
        $weeklyDays->getWednesday() && $workingDays[] = '3';
        $weeklyDays->getThursday() && $workingDays[] = '4';
        $weeklyDays->getFriday() && $workingDays[] = '5';
        $weeklyDays->getSaturday() && $workingDays[] = '6';
        $weeklyDays->getSunday() && $workingDays[] = '7';

        return $workingDays;
    }

    private function isTimeOffActivity($activity)
    {
        return \in_array($activity->getProjectId(), $this->forecastReminder->getTimeOffProjects(), true);
    }
}
