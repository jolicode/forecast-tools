<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Invoicing\DataSelector;

use App\Client\HarvestClient;
use JoliCode\Harvest\Api\Model\TimeEntryUser;

class HarvestDataSelector
{
    private $client;

    public function __construct(HarvestClient $harvestClient)
    {
        $this->client = $harvestClient;
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

    public function getTimeEntries(\DateTime $from, \DateTime $to)
    {
        $result = [];
        $timeEntries = $this->client->listTimeEntries([
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
        ], 'timeEntries')->getTimeEntries();

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
