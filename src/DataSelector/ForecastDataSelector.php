<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataSelector;

use App\Client\ForecastClient;
use App\Entity\ForecastAccount;
use JoliCode\Forecast\Api\Model\Assignment;
use JoliCode\Forecast\Api\Model\Client;
use JoliCode\Forecast\Api\Model\Person;
use JoliCode\Forecast\Api\Model\Placeholder;
use JoliCode\Forecast\Api\Model\Project;

class ForecastDataSelector
{
    public function __construct(private readonly ForecastClient $client)
    {
    }

    public function disableCache(): self
    {
        $this->client->__disableCache();

        return $this;
    }

    public function disableCacheForNextRequestOnly(): self
    {
        $this->client->__disableCacheForNextRequestOnly();

        return $this;
    }

    public function enableCache(): self
    {
        $this->client->__enableCache();

        return $this;
    }

    public function enableCacheForNextRequestOnly(): self
    {
        $this->client->__enableCacheForNextRequestOnly();

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return Assignment[]
     */
    public function getAssignments(\DateTimeInterface $from, \DateTimeInterface $to, ?array $options = []): array
    {
        $options = array_merge([
            'start_date' => $from->format('Y-m-d'),
            'end_date' => $to->format('Y-m-d'),
            'state' => 'active',
        ], $options);

        return $this->client->listAssignments($options, 'assignments')->getAssignments();
    }

    /**
     * @return Client[]
     */
    public function getClients(?bool $enabled = null): array
    {
        $clients = $this->client->listClients('clients')->getClients();

        return array_filter($clients, fn (Client $client) => null === $enabled || $enabled !== $client->getArchived());
    }

    /**
     * @return array<int|string, Client>
     */
    public function getClientsById(?bool $enabled = null): array
    {
        return self::makeLookup($this->getClients($enabled));
    }

    /**
     * @return array<string, int>
     */
    public function getClientsForChoice(?bool $enabled = null): array
    {
        $choices = [];
        $clients = $this->getClients($enabled);

        foreach ($clients as $client) {
            $choices[$client->getName()] = $client->getId();
        }

        ksort($choices);

        return $choices;
    }

    /**
     * @return array<string, int>
     */
    public function getPeopleForChoice(?bool $enabled = null): array
    {
        $choices = [];
        $people = $this->getPeople($enabled);

        foreach ($people as $person) {
            $choices[sprintf('%s %s', $person->getFirstName(), $person->getLastName())] = $person->getId();
        }

        ksort($choices);

        return $choices;
    }

    /**
     * @return array<string, int>
     */
    public function getPlaceholderForChoice(?bool $enabled = null): array
    {
        $choices = [];
        $placeholders = $this->getPlaceholders($enabled);

        foreach ($placeholders as $placeholder) {
            $choices[$placeholder->getName()] = $placeholder->getId();
        }

        ksort($choices);

        return $choices;
    }

    /**
     * @return array<string, int>
     */
    public function getProjectsForChoice(?bool $enabled = null): array
    {
        $choices = [];
        $clients = $this->getClientsById();
        $projects = $this->getProjects($enabled);

        foreach ($projects as $project) {
            if (isset($clients[$project->getClientId()])) {
                if (null === $project->getCode() || '' === $project->getCode()) {
                    $key = sprintf('%s - %s', $clients[$project->getClientId()]->getName(), $project->getName());
                } else {
                    $key = sprintf('%s - %s - %s', $clients[$project->getClientId()]->getName(), $project->getCode(), $project->getName());
                }
            } else {
                $key = $project->getName();
            }

            $choices[$key] = $project->getId();
        }

        ksort($choices);

        return $choices;
    }

    /**
     * @return Person[]
     */
    public function getPeople(?bool $enabled = null): array
    {
        $people = $this->client->listPeople('people')->getPeople();

        return array_filter($people, fn (Person $person) => null === $enabled || $enabled !== $person->getArchived());
    }

    /**
     * @return array<int|string, Person>
     */
    public function getPeopleById(?string $methodName = null, ?bool $enabled = null): array
    {
        return self::makeLookup($this->getPeople($enabled), $methodName);
    }

    /**
     * @return Placeholder[]
     */
    public function getPlaceholders(?bool $enabled = null): array
    {
        $placeholders = $this->client->listPlaceholders('placeholders')->getPlaceholders();

        return array_filter($placeholders, fn (Placeholder $placeholder) => null === $enabled || $enabled !== $placeholder->getArchived());
    }

    /**
     * @return array<int|string, Placeholder>
     */
    public function getPlaceholdersById(?string $methodName = null, ?bool $enabled = null): array
    {
        return self::makeLookup($this->getPlaceholders($enabled), $methodName);
    }

    /**
     * @return Project[]
     */
    public function getProjects(?bool $enabled = null): array
    {
        $projects = $this->client->listProjects('projects')->getProjects();

        if (null === $enabled) {
            return $projects;
        }

        return array_filter($projects, fn (Project $project) => $enabled !== $project->getArchived());
    }

    /**
     * @return array<int|string, Project>
     */
    public function getProjectsById(?string $methodName = null, ?bool $enabled = null): array
    {
        return self::makeLookup($this->getProjects($enabled), $methodName);
    }

    public function setForecastAccount(ForecastAccount $forecastAccount): self
    {
        $this->client->setForecastAccount($forecastAccount);

        return $this;
    }

    /**
     * @param array<string, mixed> $struct
     *
     * @return array<mixed, mixed>
     */
    private static function makeLookup(array $struct, ?string $methodName = null): array
    {
        if (null === $methodName) {
            $methodName = 'getId';
        }

        $lookup = [];

        foreach ($struct as $data) {
            $lookup[\call_user_func([$data, $methodName])] = $data;
        }

        return $lookup;
    }
}
