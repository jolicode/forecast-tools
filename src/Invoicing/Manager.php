<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoicing;

use App\DataSelector\ForecastDataSelector;
use App\DataSelector\HarvestDataSelector;
use App\Entity\InvoiceDueDelayRequirement;
use App\Entity\InvoiceNotesRequirement;
use App\Entity\InvoicingProcess;
use App\Repository\InvoiceExplanationRepository;
use Doctrine\Common\Collections\Collection;
use JoliCode\Harvest\Api\Model\Invoice;

class Manager
{
    final public const TIME_ENTRY_STATUS_INCOMPLETE = 'incomplete';
    final public const TIME_ENTRY_STATUS_MISSING = 'missing';
    final public const TIME_ENTRY_STATUS_OK = 'ok';
    final public const TIME_ENTRY_STATUS_OVERFLOW = 'overflow';
    final public const TIME_ENTRY_STATUS_SKIP = 'skip';
    final public const TIME_ENTRY_STATUS_WEEKEND = 'weekend';

    final public const INVOICE_EXPLAINED = 'explained';
    final public const INVOICE_OK = 'ok';
    final public const INVOICE_OTHER_MONTH = 'notice';
    final public const INVOICE_NON_RECONCILIABLE = 'notice';
    final public const INVOICE_WRONG = 'wrong';

    public function __construct(private readonly ForecastDataSelector $forecastDataSelector, private readonly HarvestDataSelector $harvestDataSelector, private readonly InvoiceExplanationRepository $invoiceExplanationRepository)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function collect(InvoicingProcess $invoicingProcess): array
    {
        $period = $this->buildDatesRange($invoicingProcess);
        $rawUsers = $this->harvestDataSelector->getEnabledUsersAsTimeEntryUsers();
        $rawTimeEntries = $this->harvestDataSelector->getUserTimeEntries(
            $invoicingProcess->getBillingPeriodStart(),
            $invoicingProcess->getBillingPeriodEnd()
        );
        $timeEntries = [];

        // filter out disabled users
        $rawTimeEntries = array_filter($rawTimeEntries, fn ($userTimeEntries, $userId) => \count(array_filter($rawUsers, fn ($user) => $user->getId() === $userId)) > 0, \ARRAY_FILTER_USE_BOTH);

        foreach ($rawTimeEntries as $userTimeEntries) {
            $timeEntry = [
                'user' => $userTimeEntries['user'],
                'entries' => [],
            ];
            $userId = $userTimeEntries['user']->getId();
            $skipErrors = $this->skipErrorsForUser($invoicingProcess, $userTimeEntries['user']->getId());

            foreach ($period as $date) {
                $key = $date->format('Y-m-d');
                $total = 0;

                if (isset($userTimeEntries['entries'][$key])) {
                    foreach ($userTimeEntries['entries'][$key] as $entry) {
                        $total += $entry->getHours();
                    }
                }

                $timeEntry['entries'][$key] = [
                    'date' => $date,
                    'total' => $total,
                    'status' => ($skipErrors ? self::TIME_ENTRY_STATUS_SKIP . ' ' : '') . $this->getTimeEntryStatus($date, $total),
                    'error' => $this->hasErrors($date, $total, $skipErrors),
                ];
            }

            $timeEntries[$userId] = $timeEntry;
        }

        foreach ($rawUsers as $user) {
            $skipErrors = $this->skipErrorsForUser($invoicingProcess, $user->getId());
            $hide = $invoicingProcess->getHarvestAccount()->getHideSkippedUsers() && $skipErrors;

            if ($hide) {
                if (isset($timeEntries[$user->getId()])) {
                    unset($timeEntries[$user->getId()]);
                }
            } else {
                if (!isset($timeEntries[$user->getId()])) {
                    $emptyEntries = [];

                    foreach ($period as $date) {
                        $key = $date->format('Y-m-d');

                        $emptyEntries[$key] = [
                            'date' => $date,
                            'total' => 0,
                            'status' => ($skipErrors ? self::TIME_ENTRY_STATUS_SKIP . ' ' : '') . $this->getTimeEntryStatus($date, 0),
                            'error' => $this->hasErrors($date, 0, $skipErrors) ? 1 : 0,
                        ];
                    }
                    $timeEntries[$user->getId()] = [
                        'user' => $user,
                        'entries' => $emptyEntries,
                    ];
                }
            }
        }

        $errorsCount = array_reduce($timeEntries, fn ($carry, $item) => $carry + array_reduce($item['entries'], fn ($before, $after) => $before + $after['error'], 0), 0);

        usort($timeEntries, fn ($a, $b): int => strcasecmp((string) $a['user']->getName(), (string) $b['user']->getName()));

        return [
            'timeEntries' => $timeEntries,
            'days' => $period,
            'errorsCount' => $errorsCount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function reconcile(InvoicingProcess $invoicingProcess): array
    {
        $diff = [];
        $totalViolations = 0;
        $period = $this->buildDatesRange($invoicingProcess);
        $rawUsers = $this->forecastDataSelector->getPeople(true);
        $rawTimeEntries = $this->harvestDataSelector->getUserTimeEntries(
            $invoicingProcess->getBillingPeriodStart(),
            $invoicingProcess->getBillingPeriodEnd()
        );
        $rawAssignments = $this->forecastDataSelector->getAssignments(
            $invoicingProcess->getBillingPeriodStart(),
            $invoicingProcess->getBillingPeriodEnd()
        );
        $projects = $this->forecastDataSelector->getProjects();

        foreach ($rawUsers as $user) {
            $skipErrors = $this->skipErrorsForUser($invoicingProcess, $user->getHarvestUserId());
            $entries = [];

            if ($skipErrors && $invoicingProcess->getHarvestAccount()->getHideSkippedUsers()) {
                continue;
            }

            foreach ($period as $date) {
                /**
                 * @var \JoliCode\Forecast\Api\Model\Assignment[]
                 */
                $forecastEntries = [];
                $key = $date->format('Y-m-d');

                if ($date->format('N') < 6) {
                    foreach ($rawAssignments as $rawAssignment) {
                        if (
                            $rawAssignment->getPersonId() === $user->getId()
                            && $rawAssignment->getStartDate() <= $date
                            && $rawAssignment->getEndDate() >= $date
                        ) {
                            $forecastEntries[] = $rawAssignment;
                        }
                    }
                }

                if (isset($rawTimeEntries[$user->getHarvestUserId()]) && isset($rawTimeEntries[$user->getHarvestUserId()]['entries'][$key])) {
                    /**
                     * @var \JoliCode\Harvest\Api\Model\TimeEntry[]
                     */
                    $harvestEntries = $rawTimeEntries[$user->getHarvestUserId()]['entries'][$key];
                } else {
                    $harvestEntries = [];
                }

                $violations = $this->computeViolations($harvestEntries, $forecastEntries, $projects, $date->format('N') >= 6);

                if (!$skipErrors) {
                    $totalViolations += $violations['violations']->count();
                }

                $entries[] = ['date' => $date, ...$violations];
            }

            $diff[$user->getId()] = [
                'entries' => $entries,
                'user' => $user,
                'skipErrors' => $skipErrors,
            ];
        }

        usort($diff, function ($a, $b): int {
            if ($a['user']->getFirstName() === $b['user']->getFirstName()) {
                return strcasecmp($a['user']->getLastName(), $b['user']->getLastName());
            }

            return strcasecmp($a['user']->getFirstName(), $b['user']->getFirstName());
        });

        return [
            'days' => $period,
            'diff' => $diff,
            'totalViolations' => $totalViolations,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function approve(InvoicingProcess $invoicingProcess): array
    {
        $period = $this->buildDatesRange($invoicingProcess);
        $rawUsers = $this->harvestDataSelector->getEnabledUsersAsTimeEntryUsers();
        $rawTimeEntries = $this->harvestDataSelector->getUserTimeEntries(
            $invoicingProcess->getBillingPeriodStart(),
            $invoicingProcess->getBillingPeriodEnd()
        );
        $timeEntries = [];
        $errorsCount = 0;

        // filter out disabled users
        $rawTimeEntries = array_filter($rawTimeEntries, fn ($userTimeEntries, $userId) => \count(array_filter($rawUsers, fn ($user) => $user->getId() === $userId)) > 0, \ARRAY_FILTER_USE_BOTH);

        foreach ($rawTimeEntries as $userTimeEntries) {
            $skipErrors = $this->skipErrorsForUser($invoicingProcess, $userTimeEntries['user']->getId());
            $hide = $skipErrors && $invoicingProcess->getHarvestAccount()->getHideSkippedUsers();

            if ($hide) {
                continue;
            }

            $timeEntry = [
                'user' => $userTimeEntries['user'],
                'entries' => [],
            ];
            $userId = $userTimeEntries['user']->getId();

            foreach ($period as $date) {
                $key = $date->format('Y-m-d');
                $isWeekend = ($date->format('N') >= 6);
                $isClosed = true;

                if (isset($userTimeEntries['entries'][$key])) {
                    foreach ($userTimeEntries['entries'][$key] as $entry) {
                        $isClosed = $isClosed && $entry->getIsClosed();
                    }
                }

                $timeEntry['entries'][$key] = [
                    'date' => $date,
                    'isClosed' => $isClosed,
                    'skipErrors' => $skipErrors,
                    'isWeekend' => $isWeekend,
                ];
                $errorsCount += !$isWeekend && !$isClosed && !$skipErrors ? 1 : 0;
            }

            $timeEntries[$userId] = $timeEntry;
        }

        usort($timeEntries, fn ($a, $b): int => strcasecmp((string) $a['user']->getName(), (string) $b['user']->getName()));

        return [
            'days' => $period,
            'errorsCount' => $errorsCount,
            'timeEntries' => $timeEntries,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function check(InvoicingProcess $invoicingProcess): array
    {
        $timeEntries = $this->harvestDataSelector->getTimeEntries(
            $invoicingProcess->getBillingPeriodStart(),
            $invoicingProcess->getBillingPeriodEnd()
        );
        $invoices = $this->harvestDataSelector->getInvoicesById(
            $invoicingProcess->getBillingPeriodStart(),
            $invoicingProcess->getBillingPeriodEnd()
        );
        $uninvoicedItems = $this->harvestDataSelector->getUninvoiced(
            $invoicingProcess->getBillingPeriodStart(),
            $invoicingProcess->getBillingPeriodEnd()
        );
        $clients = $this->harvestDataSelector->getClientsById();
        $projects = $this->harvestDataSelector->getProjectsById();
        $invoiceDueDelayRequirements = $invoicingProcess->getHarvestAccount()->getInvoiceDueDelayRequirements();
        $invoiceNotesRequirements = $invoicingProcess->getHarvestAccount()->getInvoiceNotesRequirements();
        $orphanTimeEntries = [];
        $clientInvoices = [];
        $uninvoiced = [];
        $expectedTotal = 0;
        $invoicesTotal = 0;
        $orphanExpectedTotal = 0;
        $unexplainedErrorsCount = 0;
        $uninvoicedAmountTotal = 0;
        $uninvoicedExpensesTotal = 0;
        $missingInvoiceNumbers = [];
        $invoiceNumbers = [];

        foreach ($timeEntries as $rawTimeEntry) {
            $client = $clients[$rawTimeEntry->getClient()->getId()];
            $project = $projects[$rawTimeEntry->getProject()->getId()];

            if (null !== $rawTimeEntry->getInvoice()) {
                // drop timeentries invoiced in another month
                if (isset($invoices[$rawTimeEntry->getInvoice()->getId()])) {
                    if (!isset($clientInvoices[$rawTimeEntry->getInvoice()->getId()])) {
                        $invoice = $invoices[$rawTimeEntry->getInvoice()->getId()];
                        $clientInvoices[$rawTimeEntry->getInvoice()->getId()] = [
                            'expectedTotal' => 0,
                            'timeEntries' => [],
                            'client' => $client,
                            'invoice' => $invoice,
                            'invoiceAmount' => $invoice->getAmount() - $invoice->getTaxAmount(),
                            'violations' => $this->computeInvoiceRequirementsViolations($invoice, $invoiceDueDelayRequirements, $invoiceNotesRequirements),
                        ];
                    }

                    $clientInvoices[$rawTimeEntry->getInvoice()->getId()]['timeEntries'][] = [
                        'project' => $project,
                        'timeEntry' => $rawTimeEntry,
                    ];
                    $clientInvoices[$rawTimeEntry->getInvoice()->getId()]['expectedTotal'] += $rawTimeEntry->getBillableRate() * $rawTimeEntry->getHours();
                }
            } else {
                if ($project->getIsBillable() && !$rawTimeEntry->getIsBilled()) {
                    // project is billable but the timeEntry is not associated with an invoice
                    if (!isset($orphanTimeEntries[$project->getId()])) {
                        $orphanTimeEntries[$project->getId()] = [
                            'project' => $project,
                            'timeEntries' => [],
                            'timeEntriesPerUser' => [],
                            'hours' => 0,
                            'expectedTotal' => 0,
                        ];
                        $explanation = $this->invoiceExplanationRepository->findOneBy([
                            'invoicingProcess' => $invoicingProcess,
                            'explanationKey' => 'orphan-' . $project->getId(),
                        ]);

                        if (null !== $explanation) {
                            $orphanTimeEntries[$project->getId()]['explanation'] = $explanation;
                        } else {
                            ++$unexplainedErrorsCount;
                        }
                    }

                    if (!isset($orphanTimeEntries[$project->getId()]['timeEntriesPerUser'][$rawTimeEntry->getUser()->getId()])) {
                        $orphanTimeEntries[$project->getId()]['timeEntriesPerUser'][$rawTimeEntry->getUser()->getId()] = [
                            'user' => $rawTimeEntry->getUser(),
                            'timeEntries' => [],
                            'hours' => 0,
                            'expectedTotal' => 0,
                        ];
                    }

                    $day = $rawTimeEntry->getSpentDate()->format('Y-m-d');

                    if (!isset($orphanTimeEntries[$project->getId()]['timeEntriesPerUser'][$rawTimeEntry->getUser()->getId()]['timeEntries'][$day])) {
                        $orphanTimeEntries[$project->getId()]['timeEntriesPerUser'][$rawTimeEntry->getUser()->getId()]['timeEntries'][$day] = [
                            'date' => $rawTimeEntry->getSpentDate(),
                            'hours' => 0,
                        ];
                    }

                    $timeEntryExpectedAmount = $rawTimeEntry->getHours() * $rawTimeEntry->getBillableRate();
                    $orphanTimeEntries[$project->getId()]['timeEntries'][] = $rawTimeEntry;
                    $orphanTimeEntries[$project->getId()]['hours'] += $rawTimeEntry->getHours();
                    $orphanTimeEntries[$project->getId()]['expectedTotal'] += $timeEntryExpectedAmount;
                    $orphanTimeEntries[$project->getId()]['timeEntriesPerUser'][$rawTimeEntry->getUser()->getId()]['hours'] += $rawTimeEntry->getHours();
                    $orphanTimeEntries[$project->getId()]['timeEntriesPerUser'][$rawTimeEntry->getUser()->getId()]['expectedTotal'] += $timeEntryExpectedAmount;
                    $orphanTimeEntries[$project->getId()]['timeEntriesPerUser'][$rawTimeEntry->getUser()->getId()]['timeEntries'][$day]['hours'] += $rawTimeEntry->getHours();
                    $orphanExpectedTotal += $timeEntryExpectedAmount;
                } else {
                    // remove non-billable projects
                    continue;
                }
            }
        }

        foreach ($invoices as $invoiceId => $invoice) {
            if (!isset($clientInvoices[$invoiceId])) {
                $clientInvoices[$invoiceId] = [
                    'expectedTotal' => 0,
                    'timeEntries' => [],
                    'client' => $invoice->getClient(),
                    'invoice' => $invoice,
                    'invoiceAmount' => $invoice->getAmount() - $invoice->getTaxAmount(),
                    'violations' => $this->computeInvoiceRequirementsViolations($invoice, $invoiceDueDelayRequirements, $invoiceNotesRequirements),
                ];
            }
        }

        foreach ($clientInvoices as $invoiceId => $invoice) {
            $expectedTotal += $invoice['expectedTotal'];
            $explanation = $this->invoiceExplanationRepository->findOneBy([
                'invoicingProcess' => $invoicingProcess,
                'explanationKey' => 'invoice-' . $invoice['invoice']->getNumber(),
            ]);

            $invoicesTotal += $invoice['invoiceAmount'];

            if (null !== $explanation) {
                $clientInvoices[$invoiceId]['explanation'] = $explanation;
            }

            $clientInvoices[$invoiceId]['status'] = $this->computeInvoiceStatus($clientInvoices[$invoiceId]);

            if (!\in_array($clientInvoices[$invoiceId]['status'], [self::INVOICE_EXPLAINED, self::INVOICE_OK], true)) {
                ++$unexplainedErrorsCount;
            }

            $invoiceNumbers[] = $invoice['invoice']->getNumber();
        }

        if (\count($invoiceNumbers) > 0) {
            sort($invoiceNumbers);
            $missingInvoiceNumbers = array_diff(range($invoiceNumbers[0], end($invoiceNumbers)), $invoiceNumbers);
        }

        foreach ($uninvoicedItems as $uninvoicedItem) {
            $item = [
                'uninvoiced' => $uninvoicedItem,
            ];
            $explanation = $this->invoiceExplanationRepository->findOneBy([
                'invoicingProcess' => $invoicingProcess,
                'explanationKey' => 'uninvoiced-' . $uninvoicedItem->getProjectId(),
            ]);

            if (null !== $explanation) {
                $item['explanation'] = $explanation;
            } else {
                ++$unexplainedErrorsCount;
            }

            $uninvoicedAmountTotal += $uninvoicedItem->getUninvoicedAmount();
            $uninvoicedExpensesTotal += $uninvoicedItem->getUninvoicedExpenses();

            $uninvoiced[] = $item;
        }

        usort($clientInvoices, fn ($a, $b): int => -strcmp((string) $a['invoice']->getNumber(), (string) $b['invoice']->getNumber()));

        foreach ($orphanTimeEntries as $projectId => $orphanTimeEntry) {
            foreach ($orphanTimeEntry['timeEntriesPerUser'] as $userId => $orphanTimeEntryPerUser) {
                $orphanTimeEntries[$projectId]['timeEntriesPerUser'][$userId]['daysCount'] = $orphanTimeEntries[$projectId]['timeEntriesPerUser'][$userId]['hours'] / 8;

                $timeEntries = array_filter($orphanTimeEntryPerUser['timeEntries'], fn ($item) => $item['hours'] > 0);
                ksort($timeEntries);

                $days = array_map(function ($item) {
                    $result = $item['date']->format('j');

                    if (4 === (int) $item['hours']) {
                        $result .= ' (1/2 day)';
                    } elseif (8 !== (int) $item['hours']) {
                        $result .= ' (' . $item['hours'] . ' h)';
                    }

                    return $result;
                }, $timeEntries);

                if (\count($days) > 1) {
                    $lastDay = array_pop($days);
                    $days = sprintf('%s and %s', implode(', ', $days), $lastDay);
                } else {
                    $days = current($days);
                }

                $days .= ' ' . current($timeEntries)['date']->format('F');
                $orphanTimeEntries[$projectId]['timeEntriesPerUser'][$userId]['days'] = $days;
            }
        }

        usort($orphanTimeEntries, function ($a, $b): int {
            if ($a['project']->getClient()->getName() === $b['project']->getClient()->getName()) {
                return strcasecmp($a['project']->getName(), $b['project']->getName());
            }

            return strcasecmp($a['project']->getClient()->getName(), $b['project']->getClient()->getName());
        });

        $missingInvoicesExplanation = $this->invoiceExplanationRepository->findOneBy([
            'invoicingProcess' => $invoicingProcess,
            'explanationKey' => 'missing-invoices',
        ]);

        return [
            'invoices' => $invoices,
            'clientInvoices' => $clientInvoices,
            'expectedTotal' => $expectedTotal,
            'invoicesTotal' => $invoicesTotal,
            'orphanTimeEntries' => $orphanTimeEntries,
            'orphanExpectedTotal' => $orphanExpectedTotal,
            'uninvoicedItems' => $uninvoiced,
            'unexplainedErrorsCount' => $unexplainedErrorsCount,
            'uninvoicedAmountTotal' => $uninvoicedAmountTotal,
            'uninvoicedExpensesTotal' => $uninvoicedExpensesTotal,
            'missingInvoiceNumbers' => $missingInvoiceNumbers,
            'missingInvoicesExplanation' => $missingInvoicesExplanation,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validate(InvoicingProcess $invoicingProcess): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function completed(InvoicingProcess $invoicingProcess): array
    {
        return [];
    }

    private function buildDatesRange(InvoicingProcess $invoicingProcess): \DatePeriod
    {
        $periodEnd = $invoicingProcess->getBillingPeriodEnd();

        if (!($periodEnd instanceof \DateTime)) {
            $periodEnd = \DateTime::createFromImmutable($periodEnd);
        }

        return new \DatePeriod(
            $invoicingProcess->getBillingPeriodStart(),
            new \DateInterval('P1D'),
            $periodEnd->add(new \DateInterval('P1D'))
        );
    }

    /**
     * Undocumented function.
     *
     * @param Collection<array-key, InvoiceDueDelayRequirement> $invoiceDueDelayRequirements
     * @param Collection<array-key, InvoiceNotesRequirement>    $invoiceNotesRequirements
     */
    private function computeInvoiceRequirementsViolations(Invoice $invoice, Collection $invoiceDueDelayRequirements, Collection $invoiceNotesRequirements): ViolationContainer
    {
        $violationContainer = new ViolationContainer();

        foreach ($invoiceNotesRequirements as $invoiceNotesRequirement) {
            if ($invoiceNotesRequirement->getHarvestClientId() === $invoice->getClient()->getId()) {
                if (!str_contains((string) $invoice->getNotes(), (string) $invoiceNotesRequirement->getRequirement())) {
                    $violationContainer->add(sprintf('The footnotes of the invoice must contain "%s".', $invoiceNotesRequirement->getRequirement()));
                }
            }
        }

        foreach ($invoiceDueDelayRequirements as $invoiceDueDelayRequirement) {
            if ($invoiceDueDelayRequirement->getHarvestClientId() === $invoice->getClient()->getId()) {
                $issueDate = clone $invoice->getIssueDate();
                $theoricalDueDate = $issueDate->add(new \DateInterval(sprintf('P%sD', $invoiceDueDelayRequirement->getDelay())));

                if ($theoricalDueDate > $invoice->getDueDate()) {
                    $violationContainer->add(sprintf('The due date for this invoice must be at least "%s".', $theoricalDueDate->format('F jS, Y')));
                }
            }
        }

        return $violationContainer;
    }

    /**
     * @param array<string, mixed> $invoice
     */
    private function computeInvoiceStatus(array $invoice): string
    {
        if (isset($invoice['violations']) && ($invoice['violations']->hasViolations() > 0)) {
            return self::INVOICE_WRONG;
        }

        if (isset($invoice['explanation'])) {
            return self::INVOICE_EXPLAINED;
        }

        if (isset($invoice['invoiceAmount'])) {
            if (0 === (is_countable($invoice['timeEntries']) ? \count($invoice['timeEntries']) : 0)) {
                return self::INVOICE_NON_RECONCILIABLE;
            }

            if (round($invoice['invoiceAmount'], 3) !== round($invoice['expectedTotal'], 3)) {
                return self::INVOICE_WRONG;
            }

            return self::INVOICE_OK;
        }

        return self::INVOICE_OTHER_MONTH;
    }

    /**
     * @param \JoliCode\Harvest\Api\Model\TimeEntry[]   $harvestEntries
     * @param \JoliCode\Forecast\Api\Model\Assignment[] $forecastEntries
     * @param \JoliCode\Forecast\Api\Model\Project[]    $projects
     *
     * @return array<string, mixed>
     */
    private function computeViolations(array $harvestEntries, array $forecastEntries, array $projects, bool $isWeekend): array
    {
        $mainViolationContainer = new ViolationContainer();
        $result = [
            'violations' => $mainViolationContainer,
            'forecastEntries' => [],
            'extraHarvestEntries' => [],
            'isWeekend' => $isWeekend,
        ];

        if (!$isWeekend && 0 === \count($forecastEntries)) {
            $result['violations']->add('No Forecast entry for this day. U no say what to do?');
        }

        if (\count($harvestEntries) !== \count($forecastEntries)) {
            $result['violations']->add('Not the same number of projects in Harvest and Forecast for this day.');
        }

        foreach ($forecastEntries as $forecastEntry) {
            $forecastProject = $projects[$forecastEntry->getProjectId()];
            $entry = [
                'violations' => new ViolationContainer($mainViolationContainer),
                'forecastEntry' => $forecastEntry,
                'forecastProject' => $forecastProject,
                'harvestEntry' => null,
            ];

            if (null === $forecastProject->getHarvestId()) {
                $entry['violations']->add('This Forecast project is not linked to an Harvest project.');
            } else {
                foreach ($harvestEntries as $key => $harvestEntry) {
                    if ($harvestEntry->getProject()->getId() === $forecastProject->getHarvestId()) {
                        $entry['harvestEntry'] = $harvestEntry;
                        unset($harvestEntries[$key]);
                        continue;
                    }
                }

                if (null === $entry['harvestEntry']) {
                    $entry['violations']->add('Could not find a matching Harvest project.');
                } elseif ((float) $entry['harvestEntry']->getHours() !== (float) $entry['forecastEntry']->getAllocation() / 3600) {
                    $entry['violations']->add('The assignments do not have the same duration in Harvest and in Forecast.');
                }
            }

            $result['forecastEntries'][] = $entry;
        }

        if (\count($harvestEntries) > 0) {
            $result['extraHarvestEntries'] = $harvestEntries;
            $result['violations']->add('Some assignments have been declared in Harvest but not in Forecast.');
        }

        return $result;
    }

    private function getTimeEntryStatus(\DateTimeInterface $date, int $total): string
    {
        if ($date->format('N') >= 6) {
            if ($total > 0) {
                return sprintf('%s %s', self::TIME_ENTRY_STATUS_WEEKEND, self::TIME_ENTRY_STATUS_OVERFLOW);
            }

            return self::TIME_ENTRY_STATUS_WEEKEND;
        } elseif (0 === $total) {
            return self::TIME_ENTRY_STATUS_MISSING;
        } elseif ($total < 8) {
            return self::TIME_ENTRY_STATUS_INCOMPLETE;
        } elseif ($total > 8) {
            return self::TIME_ENTRY_STATUS_OVERFLOW;
        }

        return self::TIME_ENTRY_STATUS_OK;
    }

    private function hasErrors(\DateTimeInterface $date, int $total, bool $skipErrors): bool
    {
        if ($skipErrors) {
            return false;
        }

        if (($date->format('N') >= 6) && ($total > 0) || ($date->format('N') < 6) && (8 !== $total)) {
            return true;
        }

        return false;
    }

    private function skipErrorsForUser(InvoicingProcess $invoicingProcess, ?int $userId): bool
    {
        return \in_array(
            $userId,
            $invoicingProcess->getHarvestAccount()->getDoNotCheckTimesheetsFor(),
            true
        );
    }
}
