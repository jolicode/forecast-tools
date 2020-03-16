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
use App\Entity\InvoicingProcess;
use App\Repository\InvoiceExplanationRepository;

class Manager
{
    const TIME_ENTRY_STATUS_INCOMPLETE = 'incomplete';
    const TIME_ENTRY_STATUS_MISSING = 'missing';
    const TIME_ENTRY_STATUS_OK = 'ok';
    const TIME_ENTRY_STATUS_OVERFLOW = 'overflow';
    const TIME_ENTRY_STATUS_SKIP = 'skip';
    const TIME_ENTRY_STATUS_WEEKEND = 'weekend';

    const INVOICE_EXPLAINED = 'explained';
    const INVOICE_OK = 'ok';
    const INVOICE_OTHER_MONTH = 'notice';
    const INVOICE_NON_RECONCILIABLE = 'notice';
    const INVOICE_WRONG = 'wrong';

    private $forecastDataSelector;
    private $harvestDataSelector;
    private $invoiceExplanationRepository;

    public function __construct(ForecastDataSelector $forecastDataSelector, HarvestDataSelector $harvestDataSelector, InvoiceExplanationRepository $invoiceExplanationRepository)
    {
        $this->forecastDataSelector = $forecastDataSelector;
        $this->harvestDataSelector = $harvestDataSelector;
        $this->invoiceExplanationRepository = $invoiceExplanationRepository;
    }

    public function collect(InvoicingProcess $invoicingProcess)
    {
        $period = $this->buildDatesRange($invoicingProcess);
        $rawUsers = $this->harvestDataSelector->getEnabledUsersAsTimeEntryUsers();
        $rawTimeEntries = $this->harvestDataSelector->getUserTimeEntries(
            $invoicingProcess->getBillingPeriodStart(),
            $invoicingProcess->getBillingPeriodEnd()
        );
        $timeEntries = [];

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
                            'error' => $this->hasErrors($date, 0, '' !== $skipErrors) ? 1 : 0,
                        ];
                    }
                    $timeEntries[$user->getId()] = [
                        'user' => $user,
                        'entries' => $emptyEntries,
                    ];
                }
            }
        }

        $errorsCount = array_reduce($timeEntries, function ($carry, $item) {
            return $carry + array_reduce($item['entries'], function ($before, $after) {
                return $before + $after['error'];
            }, 0);
        }, 0);

        usort($timeEntries, function ($a, $b) {
            return strcasecmp($a['user']->getName(), $b['user']->getName());
        });

        return [
            'timeEntries' => $timeEntries,
            'days' => $period,
            'errorsCount' => $errorsCount,
        ];
    }

    public function reconcile(InvoicingProcess $invoicingProcess)
    {
        $diff = [];
        $totalViolations = 0;
        $period = $this->buildDatesRange($invoicingProcess);
        $rawUsers = $this->forecastDataSelector->getPeople();
        $rawTimeEntries = $this->harvestDataSelector->getUserTimeEntries(
            $invoicingProcess->getBillingPeriodStart(),
            $invoicingProcess->getBillingPeriodEnd()
        );
        $rawAssignments = $this->forecastDataSelector->getAssignments(
            $invoicingProcess->getBillingPeriodStart(),
            $invoicingProcess->getBillingPeriodEnd()
        );
        $projects = $this->forecastDataSelector->getProjects();
        $clients = $this->forecastDataSelector->getClients();

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

                $violations = $this->computeViolations($harvestEntries, $forecastEntries, $projects, $clients, ($date->format('N') >= 6));

                if (!$skipErrors) {
                    $totalViolations += $violations['violations']->count();
                }

                $entries[] = array_merge([
                    'date' => $date,
                ], $violations);
            }

            $diff[$user->getId()] = [
                'entries' => $entries,
                'user' => $user,
                'skipErrors' => $skipErrors,
            ];
        }

        usort($diff, function ($a, $b) {
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

    public function approve(InvoicingProcess $invoicingProcess)
    {
        $period = $this->buildDatesRange($invoicingProcess);
        $rawTimeEntries = $this->harvestDataSelector->getUserTimeEntries(
            $invoicingProcess->getBillingPeriodStart(),
            $invoicingProcess->getBillingPeriodEnd()
        );
        $timeEntries = [];
        $errorsCount = 0;

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

        usort($timeEntries, function ($a, $b) {
            return strcasecmp($a['user']->getName(), $b['user']->getName());
        });

        return [
            'days' => $period,
            'errorsCount' => $errorsCount,
            'timeEntries' => $timeEntries,
        ];
    }

    public function check(InvoicingProcess $invoicingProcess)
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

            if ($rawTimeEntry->getInvoice()) {
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
                            'hours' => 0,
                            'expectedTotal' => 0,
                        ];
                        $explanation = $this->invoiceExplanationRepository->findOneBy([
                            'invoicingProcess' => $invoicingProcess,
                            'explanationKey' => 'orphan-' . $project->getId(),
                        ]);

                        if ($explanation) {
                            $orphanTimeEntries[$project->getId()]['explanation'] = $explanation;
                        } else {
                            ++$unexplainedErrorsCount;
                        }
                    }

                    $orphanTimeEntries[$project->getId()]['timeEntries'][] = $rawTimeEntry;
                    $orphanTimeEntries[$project->getId()]['hours'] += $rawTimeEntry->getHours();
                    $orphanTimeEntries[$project->getId()]['expectedTotal'] += $rawTimeEntry->getHours() * $rawTimeEntry->getBillableRate();
                    $orphanExpectedTotal += $rawTimeEntry->getHours() * $rawTimeEntry->getBillableRate();
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

            if (isset($invoice['invoiceAmount'])) {
                $invoicesTotal += $invoice['invoiceAmount'];
            }

            if ($explanation) {
                $clientInvoices[$invoiceId]['explanation'] = $explanation;
            }

            $clientInvoices[$invoiceId]['status'] = $this->computeInvoiceStatus($clientInvoices[$invoiceId]);

            if (!\in_array($clientInvoices[$invoiceId]['status'], [self::INVOICE_EXPLAINED, self::INVOICE_OK], true)) {
                ++$unexplainedErrorsCount;
            }

            $invoiceNumbers[] = $invoice['invoice']->getNumber();
        }

        if (\count($invoiceNumbers)) {
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

            if ($explanation) {
                $item['explanation'] = $explanation;
            } else {
                ++$unexplainedErrorsCount;
            }

            $uninvoicedAmountTotal += $uninvoicedItem->getUninvoicedAmount();
            $uninvoicedExpensesTotal += $uninvoicedItem->getUninvoicedExpenses();

            $uninvoiced[] = $item;
        }

        usort($clientInvoices, function ($a, $b) {
            return $a['invoice']->getNumber() < $b['invoice']->getNumber();
        });

        usort($orphanTimeEntries, function ($a, $b) {
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

    public function validate(InvoicingProcess $invoicingProcess)
    {
        return [];
    }

    public function completed(InvoicingProcess $invoicingProcess)
    {
        return [];
    }

    private function buildDatesRange(InvoicingProcess $invoicingProcess)
    {
        return new \DatePeriod(
            $invoicingProcess->getBillingPeriodStart(),
            new \DateInterval('P1D'),
            $invoicingProcess->getBillingPeriodEnd()->add(new \DateInterval('P1D'))
        );
    }

    private function computeInvoiceRequirementsViolations($invoice, $invoiceDueDelayRequirements, $invoiceNotesRequirements): ViolationContainer
    {
        $violationContainer = new ViolationContainer();

        foreach ($invoiceNotesRequirements as $invoiceNotesRequirement) {
            if ($invoiceNotesRequirement->getHarvestClientId() === $invoice->getClient()->getId()) {
                if (false === strpos($invoice->getNotes(), $invoiceNotesRequirement->getRequirement())) {
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

    private function computeInvoiceStatus(array $invoice): string
    {
        if (isset($invoice['violations']) && ($invoice['violations']->hasViolations() > 0)) {
            return self::INVOICE_WRONG;
        }

        if (isset($invoice['explanation'])) {
            return self::INVOICE_EXPLAINED;
        }

        if (isset($invoice['invoiceAmount'])) {
            if (0 === \count($invoice['timeEntries'])) {
                return self::INVOICE_NON_RECONCILIABLE;
            }

            if ($invoice['invoiceAmount'] !== $invoice['expectedTotal']) {
                return self::INVOICE_WRONG;
            }

            return self::INVOICE_OK;
        }

        return self::INVOICE_OTHER_MONTH;
    }

    /**
     * @param $harvestEntries \JoliCode\Harvest\Api\Model\TimeEntry[]
     * @param $forecastEntries \JoliCode\Forecast\Api\Model\Assignment[]
     * @param $projects \JoliCode\Forecast\Api\Model\Project[]
     * @param $clients \JoliCode\Forecast\Api\Model\Client[]
     * @param mixed $isWeekend
     */
    private function computeViolations($harvestEntries, $forecastEntries, $projects, $clients, $isWeekend): array
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

            if (!$forecastProject->getHarvestId()) {
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

        if (\count($harvestEntries)) {
            $result['extraHarvestEntries'] = $harvestEntries;
            $result['violations']->add('Some assignments have been declared in Harvest but not in Forecast.');
        }

        return $result;
    }

    private function getTimeEntryStatus(\DateTime $date, int $total): string
    {
        if ($date->format('N') >= 6) {
            if ($total > 0) {
                return sprintf('%s %s', self::TIME_ENTRY_STATUS_WEEKEND, self::TIME_ENTRY_STATUS_OVERFLOW);
            }

            return  self::TIME_ENTRY_STATUS_WEEKEND;
        } elseif (0 === $total) {
            return  self::TIME_ENTRY_STATUS_MISSING;
        } elseif ($total < 8) {
            return  self::TIME_ENTRY_STATUS_INCOMPLETE;
        } elseif ($total > 8) {
            return  self::TIME_ENTRY_STATUS_OVERFLOW;
        }

        return  self::TIME_ENTRY_STATUS_OK;
    }

    private function hasErrors(\DateTime $date, int $total, bool $skipErrors): bool
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
