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

use App\Converter\PersonToWorkingDaysConverter;
use App\Entity\ForecastReminder;
use JoliCode\Forecast\Api\Model\Assignment;
use JoliCode\Forecast\Api\Model\Client;
use JoliCode\Forecast\Api\Model\Error;
use JoliCode\Forecast\Api\Model\Person;
use JoliCode\Forecast\Api\Model\Project;

class Builder
{
    private ForecastReminder $forecastReminder;
    private $client;
    private array $clientOverrides = [];
    private array $projectOverrides = [];

    /** @var Assignment[] */
    private array $assignments = [];

    /** @var Client[] */
    private array $clients = [];

    /** @var Project[] */
    private array $projects = [];

    /** @var Person[] */
    private array $users = [];

    private bool $oneLineWithOverride = false;

    private bool $oneLineWithoutOverride = false;

    public function __construct(private PersonToWorkingDaysConverter $personToWorkingDaysConverter)
    {
    }

    public function setForecastReminder(ForecastReminder $forecastReminder)
    {
        $this->forecastReminder = $forecastReminder;
        $this->clientOverrides = self::makeLookup($forecastReminder->getClientOverrides(), 'getClientId');
        $this->projectOverrides = self::makeLookup($forecastReminder->getProjectOverrides(), 'getProjectId');
        $account = $forecastReminder->getForecastAccount();
        $this->client = \JoliCode\Forecast\ClientFactory::create(
            $account->getAccessToken(),
            (string) $account->getForecastId()
        );
    }

    public function buildMessage(\DateTime $start = null)
    {
        $report = [];
        $result = [];
        $this->oneLineWithOverride = false;
        $this->oneLineWithoutOverride = false;

        if (null === $start) {
            $start = new \DateTime('+1 day');
        }

        $end = clone $start;
        $end->modify('+2 months');

        $options = [
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
        ];

        if (!$this->fetchData($options)) {
            return false;
        }

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
            $report[$name] = $this->getActivitiesAsText($activities, $user);

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

        if (0 === \count($result)) {
            $result = ['Could not find any forecast for this day!'];
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

    public function mayNeedMoreOverrides(): bool
    {
        return $this->oneLineWithOverride && $this->oneLineWithoutOverride;
    }

    private static function makeLookup($struct, $methodName = 'getId')
    {
        $lookup = [];

        foreach ($struct as $data) {
            $lookup[\call_user_func([$data, $methodName])] = $data;
        }

        return $lookup;
    }

    private function fetchData($options): bool
    {
        $users = $this->client->listPeople();

        if (Error::class === \get_class($users)) {
            return false;
        }

        $users = $users->getPeople();
        $users = array_values(array_filter($users, function ($user): bool {
            return false === $user->getArchived();
        }));
        usort($users, function ($a, $b): int {
            if ($a->getFirstName() === $b->getFirstName()) {
                return strcmp($a->getLastName(), $b->getLastName());
            }

            return strcmp($a->getFirstName(), $b->getFirstName());
        });

        $this->assignments = $this->client->listAssignments($options)->getAssignments();
        $this->clients = self::makeLookup($this->client->listClients()->getClients());
        $this->projects = self::makeLookup($this->client->listProjects()->getProjects());
        $this->users = $users;

        return true;
    }

    private function getActivitiesAsText($activities, Person $user)
    {
        if (0 === \count($activities)) {
            return $this->forecastReminder->getDefaultActivityName() ?? 'not set';
        }

        if (1 === \count($activities) && $this->isTimeOffActivity($activities[0])) {
            $endDate = $this->getTimeOffEndDate($user);
            $timeOffActivityName = $this->forecastReminder->getTimeOffActivityName() ?? 'holidays (until %s)';

            if (null !== $this->forecastReminder->getDefaultActivityName() && $activities[0]->getAllocation() < 8 * 3600) {
                $timeOffActivityName .= ' and ' . $this->forecastReminder->getDefaultActivityName();
            }

            return sprintf($timeOffActivityName, $endDate->format('Y-m-d'));
        }

        $activities = array_unique(array_map(function ($activity) {
            if (isset($this->projectOverrides[$activity->getProjectId()])) {
                $this->oneLineWithOverride = true;

                return $this->projectOverrides[$activity->getProjectId()];
            }

            $project = $this->projects[$activity->getProjectId()];

            if (isset($this->clientOverrides[$project->getClientId()])) {
                $this->oneLineWithOverride = true;

                return $this->clientOverrides[$project->getClientId()];
            }

            $this->oneLineWithoutOverride = true;

            if ((null !== $project->getClientId()) && \array_key_exists($project->getClientId(), $this->clients)) {
                $client = $this->clients[$project->getClientId()];

                return $client->getName() . ' | ' . $project->getName();
            }

            return $project->getName();
        }, $activities));

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
        $workingDays = $this->personToWorkingDaysConverter->convert($user);

        if (!\in_array($date->format('N'), $workingDays, true)) {
            return [];
        }

        $activities = $this->getPersonActivities($user);

        return array_values(array_filter($activities, function ($activity) use ($date): bool {
            return $activity->getStartDate()->format('Y-m-d') <= $date->format('Y-m-d') && $activity->getEndDate()->format('Y-m-d') >= $date->format('Y-m-d');
        }));
    }

    /**
     * @param mixed $user
     *
     * @return Assignment[]
     */
    private function getPersonActivities($user)
    {
        return array_values(array_filter($this->assignments, function ($activity) use ($user): bool {
            return $activity->getPersonId() === $user->getId();
        }));
    }

    private function getTimeOffEndDate(Person $user)
    {
        $activities = $this->getPersonActivities($user);
        $activities = array_values(array_filter($activities, function ($activity): bool {
            return $this->isTimeOffActivity($activity);
        }));
        $activities = array_map(function ($activity): Assignment {
            $endDate = clone $activity->getEndDate();

            if ($endDate->format('N') >= 5) {
                $endDate->modify('next monday');
                $activity->setEndDate($endDate);
            }

            return $activity;
        }, $activities);
        usort($activities, function ($a, $b): int {
            return ($a->getEndDate() < $b->getEndDate()) ? 1 : -1;
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

    private function isTimeOffActivity($activity)
    {
        return \in_array($activity->getProjectId(), $this->forecastReminder->getTimeOffProjects(), true);
    }
}
