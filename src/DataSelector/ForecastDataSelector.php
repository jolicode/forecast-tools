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
    private $client;

    public function __construct(ForecastClient $forecastClient)
    {
        $this->client = $forecastClient;
    }

    /**
     * @return Assignment[]
     */
    public function getAssignments(\DateTime $from, \DateTime $to)
    {
        return $this->client->listAssignments([
            'start_date' => $from->format('Y-m-d'),
            'end_date' => $to->format('Y-m-d'),
            'state' => 'active',
        ], 'assignments')->getAssignments();
    }

    /**
     * @return Client[]
     */
    public function getClients()
    {
        return $this->client->listClients('clients')->getClients();
    }

    /**
     * @return Client[]
     */
    public function getClientsById()
    {
        return self::makeLookup($this->getClients());
    }

    public function getEnabledClientsForChoice(): array
    {
        $choices = [];
        $clients = $this->getClients();

        foreach ($clients as $key => $client) {
            if (!$client->getArchived()) {
                $choices[$client->getName()] = $client->getId();
            }
        }

        ksort($choices);

        return $choices;
    }

    public function getEnabledPeopleForChoice(): array
    {
        $choices = [];
        $people = $this->getPeople();

        foreach ($people as $person) {
            $choices[sprintf('%s %s', $person->getFirstName(), $person->getLastName())] = $person->getId();
        }

        ksort($choices);

        return $choices;
    }

    public function getEnabledProjectsForChoice(): array
    {
        $choices = [];
        $clients = $this->getClientsById();
        $projects = $this->getProjects();

        foreach ($projects as $project) {
            if (!$project->getArchived()) {
                if (isset($clients[$project->getClientId()])) {
                    $key = sprintf('%s - %s%s', $clients[$project->getClientId()]->getName(), $project->getCode() ? $project->getCode() . ' - ' : '', $project->getName());
                } else {
                    $key = $project->getName();
                }

                $choices[$key] = $project->getId();
            }
        }

        ksort($choices);

        return $choices;
    }

    /**
     * @return Person[]
     */
    public function getPeople()
    {
        $people = $this->client->listPeople('people')->getPeople();

        return array_filter($people, function (Person $item) {
            return !$item->getArchived();
        });
    }

    /**
     * @return Client[]
     */
    public function getPeopleById()
    {
        return self::makeLookup($this->getPeople());
    }

    /**
     * @return Placeholder[]
     */
    public function getPlaceholders()
    {
        return $this->client->listPlaceholders('placeholders')->getPlaceholders();
    }

    /**
     * @return Placeholder[]
     */
    public function getPlaceholdersById()
    {
        return self::makeLookup($this->getPlaceholders());
    }

    /**
     * @return Project[]
     */
    public function getProjects()
    {
        return $this->client->listProjects('projects')->getProjects();
    }

    /**
     * @return Client[]
     */
    public function getProjectsById()
    {
        return self::makeLookup($this->getProjects());
    }

    public function setForecastAccount(ForecastAccount $forecastAccount)
    {
        $this->client->setForecastAccount($forecastAccount);
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
