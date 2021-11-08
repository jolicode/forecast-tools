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
use App\Entity\HarvestAccount;
use JoliCode\Harvest\Api\Model\Client;
use JoliCode\Harvest\Api\Model\Invoice;
use JoliCode\Harvest\Api\Model\Project;
use JoliCode\Harvest\Api\Model\TaskAssignment;
use JoliCode\Harvest\Api\Model\TimeEntry;
use JoliCode\Harvest\Api\Model\TimeEntryUser;
use JoliCode\Harvest\Api\Model\UninvoicedReportResult;
use JoliCode\Harvest\Api\Model\User;

class HarvestDataSelector
{
    private $client;

    public function __construct(HarvestClient $harvestClient)
    {
        $this->client = $harvestClient;
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

    public function setHarvestAccount(HarvestAccount $harvestAccount): self
    {
        $this->client->__client($harvestAccount);

        return $this;
    }

    public function enableCacheForNextRequestOnly(): self
    {
        $this->client->__enableCacheForNextRequestOnly();

        return $this;
    }

    /**
     * @return Client[]
     */
    public function getClients(): array
    {
        return $this->client->listClients([], 'clients')->getClients();
    }

    /**
     * @return Client[]
     */
    public function getClientsById(): array
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
    public function getEnabledClients(): array
    {
        return array_filter($this->getClientsById(), function (Client $client): ?bool {
            return $client->getIsActive();
        });
    }

    public function getEnabledClientsForChoice(): array
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

    /**
     * @return User[]
     */
    public function getEnabledUsers(): array
    {
        $users = $this->client->listUsers(['is_active' => true], 'users')->getUsers();

        foreach ($users as $key => $user) {
            if (!$user->getIsActive()) {
                unset($users[$key]);
            }
        }

        usort($users, function ($a, $b): int {
            if ($a->getFirstName() === $b->getFirstName()) {
                return strcmp($a->getLastName(), $b->getLastName());
            }

            return strcmp($a->getFirstName(), $b->getFirstName());
        });

        return $users;
    }

    public function getUserByEmail(string $email): ?User
    {
        $users = array_filter($this->getEnabledUsers(), function (User $user) use ($email): bool {
            return $email === $user->getEmail();
        });

        if (\count($users) > 0) {
            return array_pop($users);
        }

        return null;
    }

    /**
     * @return TimeEntryUser[]
     */
    public function getEnabledUsersAsTimeEntryUsers(): array
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

    public function getEnabledUsersForChoice(): array
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
    public function getInvoices(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->client->listInvoices([
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ], 'invoices')->getInvoices();
    }

    /**
     * @return Invoice[]
     */
    public function getInvoicesById(\DateTimeInterface $from, \DateTimeInterface $to): array
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
    public function getProjects(): array
    {
        return $this->client->listProjects([], 'projects')->getProjects();
    }

    /**
     * @return Project[]
     */
    public function getProjectsById(): array
    {
        $projectsById = [];
        $projects = $this->getProjects();

        foreach ($projects as $project) {
            $projectsById[$project->getId()] = $project;
        }

        return $projectsById;
    }

    /**
     * @param mixed $projectId
     *
     * @return TaskAssignment[]
     */
    public function getTaskAssignmentsForProjectId($projectId): array
    {
        $taskAssignmentsById = [];
        $taskAssignments = $this->client->listTaskAssignmentsForSpecificProject((string) $projectId, 'taskAssignments')->getTaskAssignments();

        foreach ($taskAssignments as $taskAssignment) {
            $taskAssignmentsById[$taskAssignment->getId()] = $taskAssignment;
        }

        return $taskAssignmentsById;
    }

    /**
     * @return TimeEntry[]
     */
    public function getTimeEntries(\DateTimeInterface $from, \DateTimeInterface $to, array $options = []): array
    {
        $options = array_merge([
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ], $options);

        return $this->client->listTimeEntries($options, 'timeEntries')->getTimeEntries();
    }

    /**
     * @return UninvoicedReportResult[]
     */
    public function getUninvoiced(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $uninvoiced = $this->client->uninvoicedReport([
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ], 'results')->getResults();
        $uninvoiced = array_filter($uninvoiced, function (UninvoicedReportResult $a): bool {
            return ($a->getUninvoicedAmount() + $a->getUninvoicedExpenses()) > 0;
        });

        return $uninvoiced;
    }

    public function getUserAssignments(array $options = []): array
    {
        $result = [];
        $assignments = $this->client->listUserAssignments($options, 'userAssignments')->getUserAssignments();

        foreach ($assignments as $assignment) {
            $userId = $assignment->getUser()->getId();
            $projectId = $assignment->getProject()->getId();

            if (!isset($result[$userId])) {
                $result[$userId] = [];
            }

            $result[$userId][$projectId] = $assignment;
        }

        return $result;
    }

    public function getUserTimeEntries(\DateTimeInterface $from, \DateTimeInterface $to, array $options = []): array
    {
        $result = [];
        $timeEntries = $this->getTimeEntries($from, $to, $options);

        foreach ($timeEntries as $timeEntry) {
            $day = $timeEntry->getSpentDate()->format('Y-m-d');
            $user = $timeEntry->getUser();
            $userId = $user->getId();

            if (!isset($result[$userId])) {
                $result[$userId] = [
                    'entries' => [],
                    'user' => $user,
                ];
            }

            if (!isset($result[$userId]['entries'][$day])) {
                $result[$userId]['entries'][$day] = [];
            }

            $result[$userId]['entries'][$day][] = $timeEntry;
        }

        return $result;
    }
}
