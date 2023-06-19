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
use App\Entity\ClientOverride;
use App\Entity\ForecastReminder;
use App\Entity\ProjectOverride;
use Doctrine\Common\Collections\Collection;
use JoliCode\Forecast\Api\Model\Assignment;
use JoliCode\Forecast\Api\Model\Client;
use JoliCode\Forecast\Api\Model\Error;
use JoliCode\Forecast\Api\Model\Person;
use JoliCode\Forecast\Api\Model\Project;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Builder
{
    private ForecastReminder $forecastReminder;
    private ?\JoliCode\Forecast\Client $client = null;

    /**
     * @var array<int, ClientOverride>
     */
    private array $clientOverrides = [];

    /**
     * @var array<int, ProjectOverride>
     */
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

    public function __construct(
        private readonly PersonToWorkingDaysConverter $personToWorkingDaysConverter,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildBlocks(ForecastReminder $forecastReminder, \DateTime $start = null): array
    {
        $this->setForecastReminder($forecastReminder);
        $title = $this->buildTitle($start);
        $message = $this->buildMessage($start);

        if (null === $message) {
            $message = 'An error occured, could not compute the forecast.';
            $successful = false;
        } else {
            $successful = true;
        }

        $payload = [
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => $title,
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => $message,
                    ],
                ],
            ],
            'successful' => $successful,
        ];

        if ($this->mayNeedMoreOverrides()) {
            $payload['blocks'][] = [
                'type' => 'context',
                'elements' => [[
                    'type' => 'mrkdwn',
                    'text' => sprintf(
                        'Missing an override? <%s|Add it in Forecast tools!>',
                        $this->urlGenerator->generate(
                            'organization_reminder_index',
                            ['slug' => $this->forecastReminder->getForecastAccount()->getSlug()],
                            UrlGeneratorInterface::ABSOLUTE_URL
                        )
                    ),
                ]],
            ];
        }

        return $payload;
    }

    private function buildMessage(\DateTime $start = null): ?string
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
            return null;
        }

        $longuestNameLength = 0;

        foreach ($this->users as $user) {
            if (\count((array) $this->forecastReminder->getOnlyUsers()) > 0 && !\in_array($user->getId(), $this->forecastReminder->getOnlyUsers(), true)) {
                continue;
            }

            if (\count((array) $this->forecastReminder->getExceptUsers()) > 0 && \in_array($user->getId(), $this->forecastReminder->getExceptUsers(), true)) {
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

    private function buildTitle(\DateTime $startDate = null): string
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

    /**
     * @param array<string, mixed> $options
     */
    private function fetchData($options): bool
    {
        $users = $this->client->listPeople();

        if (Error::class === $users::class) {
            return false;
        }

        $users = $users->getPeople();
        $users = array_values(array_filter($users, fn ($user): bool => false === $user->getArchived()));
        usort($users, function ($a, $b): int {
            if ($a->getFirstName() === $b->getFirstName()) {
                return strcmp((string) $a->getLastName(), (string) $b->getLastName());
            }

            return strcmp((string) $a->getFirstName(), (string) $b->getFirstName());
        });

        $this->assignments = $this->client->listAssignments($options)->getAssignments();
        $this->clients = self::makeLookup($this->client->listClients()->getClients());
        $this->projects = self::makeLookup($this->client->listProjects()->getProjects());
        $this->users = $users;

        return true;
    }

    /**
     * @param Assignment[] $activities
     */
    private function getActivitiesAsText(array $activities, Person $user): string
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

                return $this->projectOverrides[$activity->getProjectId()]->getName();
            }

            $project = $this->projects[$activity->getProjectId()];

            if (isset($this->clientOverrides[$project->getClientId()])) {
                $this->oneLineWithOverride = true;

                return $this->clientOverrides[$project->getClientId()]->getName();
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

    /**
     * @return Assignment[]
     */
    private function getActivity(Person $user, \DateTime $date): array
    {
        $workingDays = $this->personToWorkingDaysConverter->convert($user);

        if (!\in_array($date->format('N'), $workingDays, true)) {
            return [];
        }

        $activities = $this->getPersonActivities($user);

        return array_values(array_filter($activities, fn ($activity): bool => $activity->getStartDate()->format('Y-m-d') <= $date->format('Y-m-d') && $activity->getEndDate()->format('Y-m-d') >= $date->format('Y-m-d')));
    }

    /**
     * @return Assignment[]
     */
    private function getPersonActivities(mixed $user): array
    {
        return array_values(array_filter($this->assignments, fn ($activity): bool => $activity->getPersonId() === $user->getId()));
    }

    private function getTimeOffEndDate(Person $user): ?\DateTime
    {
        $activities = $this->getPersonActivities($user);
        $activities = array_values(array_filter($activities, fn ($activity): bool => $this->isTimeOffActivity($activity)));
        $activities = array_map(function (Assignment $activity): Assignment {
            $endDate = clone $activity->getEndDate();

            if ($endDate->format('N') >= 5) {
                $endDate->modify('next monday');
                $activity->setEndDate($endDate);
            }

            return $activity;
        }, $activities);
        usort($activities, fn ($a, $b): int => ($a->getEndDate() < $b->getEndDate()) ? 1 : -1);
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

    private function isTimeOffActivity(Assignment $activity): bool
    {
        return \in_array($activity->getProjectId(), $this->forecastReminder->getTimeOffProjects(), true);
    }

    /**
     * @param Collection<int, ClientOverride>|Collection<int, ProjectOverride>|Project[]|Client[] $struct
     *
     * @return array<int, object>
     */
    private static function makeLookup(array|Collection $struct, ?string $methodName = 'getId'): array
    {
        $lookup = [];

        foreach ($struct as $data) {
            $lookup[\call_user_func([$data, $methodName])] = $data;
        }

        return $lookup;
    }

    private function mayNeedMoreOverrides(): bool
    {
        return $this->oneLineWithOverride && $this->oneLineWithoutOverride;
    }

    private function setForecastReminder(ForecastReminder $forecastReminder): void
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
}
