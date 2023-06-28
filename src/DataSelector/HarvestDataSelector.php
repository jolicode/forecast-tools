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
use JoliCode\Forecast\Api\Model\Client as ForecastClient;
use JoliCode\Forecast\Api\Model\Project as ForecastProject;
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
    public function __construct(
        private readonly HarvestClient $client,
        private readonly ForecastDataSelector $forecastDataSelector,
    ) {
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
    public function getClients(bool $isActive = null): array
    {
        $params = [];

        if (null !== $isActive) {
            $params['is_active'] = $isActive;
        }

        return $this->client->listClients($params, 'clients')->getClients();
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
        return $this->getClients(true);
    }

    /**
     * @return array<string, int>
     */
    public function getClientsForChoice(bool $enabled = null): array
    {
        $choices = [];
        $clients = $this->getClients();

        foreach ($clients as $client) {
            if (null === $enabled || $enabled === $client->getIsActive()) {
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
                return strcmp((string) $a->getLastName(), (string) $b->getLastName());
            }

            return strcmp((string) $a->getFirstName(), (string) $b->getFirstName());
        });

        return $users;
    }

    public function getUserByEmail(string $email): ?User
    {
        $users = array_filter($this->getEnabledUsers(), fn (User $user): bool => $email === $user->getEmail());

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

    /**
     * @return array<string, int>
     */
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
    public function getInvoices(\DateTimeInterface $from = null, \DateTimeInterface $to = null, string $state = null): array
    {
        $params = [];

        if ($from instanceof \DateTimeInterface) {
            $params['from'] = $from->format('Y-m-d');
        }

        if ($to instanceof \DateTimeInterface) {
            $params['to'] = $to->format('Y-m-d');
        }

        if (null !== $state) {
            $params['state'] = $state;
        }

        return $this->client->listInvoices($params, 'invoices')->getInvoices();
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
     * @return array<string, Client>
     */
    public function getOutdatedClients(): array
    {
        $this->client->__disableCacheForNextRequestOnly();
        $activeProjects = $this->getProjects(true);

        $this->client->__disableCacheForNextRequestOnly();
        $clients = self::makeLookup($this->getClients(true), 'getName');
        ksort($clients);

        return array_filter($clients, function (Client $client) use ($activeProjects): bool {
            foreach ($activeProjects as $project) {
                if (null !== $project->getClient() && $project->getClient()->getId() === $client->getId()) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * @param array<array-key, int> $doNotCleanupClientIds
     *
     * @return array<string, array<string, mixed>>
     */
    public function getOutdatedProjects(array $doNotCleanupClientIds): array
    {
        $forecastClients = $this->forecastDataSelector->getClients(true);
        $forecastProjects = $this->forecastDataSelector->getProjects(true);
        $outdatedProjects = [];
        $invoices = $this->getInvoices((new \DateTime())->modify('-3 years'));
        $createdAtLimit = (new \DateTime())->modify('-5 months');
        $lastInvoiceIssueDateLimit = (new \DateTime())->modify('-2 months');

        $this->client->__disableCacheForNextRequestOnly();

        foreach ($this->getProjects(true) as $project) {
            if (!\in_array($project->getClient()->getId(), $doNotCleanupClientIds, true) && $project->getCreatedAt() < $createdAtLimit) {
                // search if there were time entries during the last 6 months on this project
                $timeEntries = $this->getTimeEntries(
                    (new \DateTime())->modify('-4 months'),
                    (new \DateTime())->modify('+6 months'),
                    [
                        'project_id' => $project->getId(),
                    ],
                );

                if (\count($timeEntries) > 0) {
                    // there were time entries during the last 4 months, so we don't delete this project
                    continue;
                }

                $label = sprintf('%s - %s',
                    $project->getClient()->getName(),
                    $project->getName(),
                );

                if (null !== $project->getCode() && '' !== $project->getCode()) {
                    $label = sprintf('[%s] %s', $project->getCode(), $label);
                }

                $lastProjectInvoices = array_filter($invoices, function (Invoice $invoice) use ($project) {
                    foreach ($invoice->getLineItems() as $lineItem) {
                        if ($lineItem->getProject() && $lineItem->getProject()->getId() === $project->getId()) {
                            return true;
                        }
                    }

                    return false;
                });
                usort($lastProjectInvoices, fn (Invoice $a, Invoice $b) => $b->getIssueDate()->getTimestamp() <=> $a->getIssueDate()->getTimestamp());
                $projectOpenInvoices = array_filter($lastProjectInvoices, fn (Invoice $invoice) => \in_array($invoice->getState(), ['draft', 'open'], true));

                if (\count($projectOpenInvoices) > 0) {
                    // if there are open invoices, we don't consider the project as outdated
                    continue;
                }

                if (\count($lastProjectInvoices) > 0 && $lastProjectInvoices[0]->getIssueDate() > $lastInvoiceIssueDateLimit) {
                    // if the last invoice was issued less than 2 months ago, we don't consider the project as outdated
                    continue;
                }

                $startDate = \count($lastProjectInvoices) > 0 ? clone $lastProjectInvoices[0]->getIssueDate() : new \DateTime();
                $forecastSearch = false;
                $forecastProject = array_filter($forecastProjects, fn (ForecastProject $forecastProject) => $forecastProject->getHarvestId() === $project->getId());

                if (\count($forecastProject) > 0) {
                    $forecastProject = array_pop($forecastProject);
                    $forecastSearch = $forecastProject->getName();
                    $forecastClient = array_filter($forecastClients, fn (ForecastClient $forecastClient) => $forecastClient->getId() === $forecastProject->getClientId());

                    if (\count($forecastClient) > 0) {
                        $forecastSearch = sprintf('%s %s', array_pop($forecastClient)->getName(), $forecastSearch);
                    }
                }

                $outdatedProjects[$label] = [
                    'project' => $project,
                    'invoices' => $lastProjectInvoices,
                    'forecastSearch' => $forecastSearch,
                    'startDate' => $startDate->modify('-75 days')->format('Y-m-d'),
                ];
            }
        }

        return $outdatedProjects;
    }

    /**
     * @return Project[]
     */
    public function getProjects(bool $isActive = null): array
    {
        $params = [];

        if (null !== $isActive) {
            $params['is_active'] = $isActive;
        }

        return $this->client->listProjects($params, 'projects')->getProjects();
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
     * @return TaskAssignment[]
     */
    public function getTaskAssignmentsForProjectId(mixed $projectId): array
    {
        $taskAssignmentsById = [];
        $taskAssignments = $this->client->listTaskAssignmentsForSpecificProject((string) $projectId, 'taskAssignments')->getTaskAssignments();

        foreach ($taskAssignments as $taskAssignment) {
            $taskAssignmentsById[$taskAssignment->getId()] = $taskAssignment;
        }

        return $taskAssignmentsById;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return TimeEntry[]
     */
    public function getTimeEntries(\DateTimeInterface $from, \DateTimeInterface $to, ?array $options = []): array
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
        $uninvoiced = array_filter($uninvoiced, fn (UninvoicedReportResult $a): bool => ($a->getUninvoicedAmount() + $a->getUninvoicedExpenses()) > 0);

        return $uninvoiced;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<int|string, array<int|string, \JoliCode\Harvest\Api\Model\UserAssignment>>
     */
    public function getUserAssignments(?array $options = []): array
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

    /**
     * @param array<string, mixed> $options
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUserTimeEntries(\DateTimeInterface $from, \DateTimeInterface $to, ?array $options = []): array
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

    /**
     * @param array<string, mixed> $struct
     *
     * @return array<mixed, mixed>
     */
    private static function makeLookup(array $struct, string $methodName = null): array
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
