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

use App\Client\HarvestClient;
use JoliCode\Harvest\Api\Model\Client;
use JoliCode\Harvest\Api\Model\Invoice;
use JoliCode\Harvest\Api\Model\Project;
use JoliCode\Harvest\Api\Model\TimeEntry;
use JoliCode\Harvest\Api\Model\TimeEntryUser;
use JoliCode\Harvest\Api\Model\UninvoicedReportResult;

class HarvestDataSelector
{
    private $client;

    public function __construct(HarvestClient $harvestClient)
    {
        $this->client = $harvestClient;
    }

    /**
     * @return Client[]
     */
    public function getClients()
    {
        return $this->client->listClients([], 'clients')->getClients();
    }

    /**
     * @return Client[]
     */
    public function getClientsById()
    {
        $clientsById = [];
        $clients = $this->getClients();

        foreach ($clients as $client) {
            $clientsById[$client->getId()] = $client;
        }

        return $clientsById;
    }

    /**
     * @return Client[]
     */
    public function getEnabledClients()
    {
        return array_filter($this->getClientsById(), function (Client $client) {
            return $client->getIsActive();
        });
    }

    public function getEnabledClientsForChoice()
    {
        $choices = [];
        $clients = $this->getClients();

        foreach ($clients as $key => $client) {
            if ($client->getIsActive()) {
                $choices[$client->getName()] = $client->getId();
            }
        }

        ksort($choices);

        return $choices;
    }

    public function getEnabledUsers()
    {
        $users = $this->client->listUsers(['is_active' => true], 'users')->getUsers();

        foreach ($users as $key => $user) {
            if (!$user->getIsActive()) {
                unset($users[$key]);
            }
        }

        usort($users, function ($a, $b) {
            if ($a->getFirstName() === $b->getFirstName()) {
                return $a->getLastName() > $b->getLastName();
            }

            return $a->getFirstName() > $b->getFirstName();
        });

        return $users;
    }

    public function getEnabledUsersAsTimeEntryUsers()
    {
        $timeEntryUsers = [];
        $users = $this->getEnabledUsers();

        foreach ($users as $user) {
            $timeEntryUser = new TimeEntryUser();
            $timeEntryUser->setName(sprintf('%s %s', $user->getFirstName(), $user->getLastName()));
            $timeEntryUser->setId($user->getId());
            $timeEntryUsers[] = $timeEntryUser;
        }

        return $timeEntryUsers;
    }

    public function getEnabledUsersForChoice()
    {
        $choices = [];
        $users = $this->client->listUsers(['is_active' => true], 'users')->getUsers();

        foreach ($users as $user) {
            if ($user->getIsActive()) {
                $choices[$user->getFirstName() . ' ' . $user->getLastName()] = $user->getId();
            }
        }

        ksort($choices);

        return $choices;
    }

    /**
     * @return Invoice[]
     */
    public function getInvoices(\DateTime $from, \DateTime $to)
    {
        return $this->client->listInvoices([
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ], 'invoices')->getInvoices();
    }

    /**
     * @return Invoice[]
     */
    public function getInvoicesById(\DateTime $from, \DateTime $to)
    {
        $invoicesById = [];
        $invoices = $this->getInvoices($from, $to);

        foreach ($invoices as $invoice) {
            $invoicesById[$invoice->getId()] = $invoice;
        }

        return $invoicesById;
    }

    /**
     * @return Project[]
     */
    public function getProjects()
    {
        return $this->client->listProjects([], 'projects')->getProjects();
    }

    /**
     * @return Project[]
     */
    public function getProjectsById()
    {
        $projectsById = [];
        $projects = $this->getProjects();

        foreach ($projects as $project) {
            $projectsById[$project->getId()] = $project;
        }

        return $projectsById;
    }

    /**
     * @return TimeEntry[]
     */
    public function getTimeEntries(\DateTime $from, \DateTime $to)
    {
        return $this->client->listTimeEntries([
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ], 'timeEntries')->getTimeEntries();
    }

    /**
     * @return UninvoicedReportResult[]
     */
    public function getUninvoiced(\DateTime $from, \DateTime $to)
    {
        $uninvoiced = $this->client->uninvoicedReport([
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ], 'results')->getResults();
        $uninvoiced = array_filter($uninvoiced, function (UninvoicedReportResult $a) {
            return ($a->getUninvoicedAmount() + $a->getUninvoicedExpenses()) > 0;
        });

        return $uninvoiced;
    }

    public function getUserTimeEntries(\DateTime $from, \DateTime $to)
    {
        $result = [];
        $timeEntries = $this->getTimeEntries($from, $to);

        foreach ($timeEntries as $timeEntry) {
            $day = $timeEntry->getSpentDate()->format('Y-m-d');

            if (!isset($result[$timeEntry->getUser()->getId()])) {
                $result[$timeEntry->getUser()->getId()] = [
                    'entries' => [],
                    'user' => $timeEntry->getUser(),
                ];
            }

            if (!isset($result[$timeEntry->getUser()->getId()]['entries'][$day])) {
                $result[$timeEntry->getUser()->getId()]['entries'][$day] = [];
            }

            $result[$timeEntry->getUser()->getId()]['entries'][$day][] = $timeEntry;
        }

        return $result;
    }
}
