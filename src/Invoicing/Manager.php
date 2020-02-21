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

use App\Entity\InvoicingProcess;
use App\Invoicing\DataSelector\ForecastDataSelector;
use App\Invoicing\DataSelector\HarvestDataSelector;

class Manager
{
    const TIME_ENTRY_STATUS_INCOMPLETE = 'incomplete';
    const TIME_ENTRY_STATUS_MISSING = 'missing';
    const TIME_ENTRY_STATUS_OK = 'ok';
    const TIME_ENTRY_STATUS_OVERFLOW = 'overflow';
    const TIME_ENTRY_STATUS_SKIP = 'skip';
    const TIME_ENTRY_STATUS_WEEKEND = 'weekend';

    private $forecastDataSelector;
    private $harvestDataSelector;

    public function __construct(ForecastDataSelector $forecastDataSelector, HarvestDataSelector $harvestDataSelector)
    {
        $this->forecastDataSelector = $forecastDataSelector;
        $this->harvestDataSelector = $harvestDataSelector;
    }

    public function collect(InvoicingProcess $invoicingProcess)
    {
        $period = $this->buildDatesRange($invoicingProcess);
        $rawUsers = $this->harvestDataSelector->getEnabledUsersAsTimeEntryUsers();
        $rawTimeEntries = $this->harvestDataSelector->getTimeEntries(
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
            $hide = $invoicingProcess->getHarvestAccount()->getHideSkippedUsers() && $this->skipErrorsForUser($invoicingProcess, $user->getId());

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
                            'status' => $skipErrors . $this->getTimeEntryStatus($date, 0),
                            'error' => $this->hasErrors($date, $total, '' !== $skipErrors) ? 1 : 0,
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
            return $a['user']->getName() > $b['user']->getName();
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
        $rawTimeEntries = $this->harvestDataSelector->getTimeEntries(
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
                $totalViolations += $violations['violations']->count();
                $entries[] = array_merge([
                    'date' => $date,
                ], $violations);
            }
            dump($totalViolations);

            $diff[$user->getId()] = [
                'entries' => $entries,
                'user' => $user,
                'skipErrors' => $skipErrors,
            ];
        }

        usort($diff, function ($a, $b) {
            if ($a['user']->getFirstName() === $b['user']->getFirstName()) {
                return $a['user']->getLastName() > $b['user']->getLastName();
            }

            return $a['user']->getFirstName() > $b['user']->getFirstName();
        });

        return [
            'days' => $period,
            'diff' => $diff,
            'totalViolations' => $totalViolations,
        ];
    }

    public function approve(InvoicingProcess $invoicingProcess)
    {
        return [];
    }

    public function check(InvoicingProcess $invoicingProcess)
    {
        return [];
    }

    public function validate(InvoicingProcess $invoicingProcess)
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

    private function skipErrorsForUser(InvoicingProcess $invoicingProcess, int $userId): bool
    {
        return \in_array(
            $userId,
            $invoicingProcess->getHarvestAccount()->getDoNotCheckTimesheetsFor(),
            true
        );
    }
}
