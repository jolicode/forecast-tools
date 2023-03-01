<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Converter;

use JoliCode\Forecast\Api\Model\Person;

class PersonToWorkingDaysConverter
{
    /**
     * @return array<string>
     */
    public function convert(?Person $person = null): array
    {
        if (null === $person || null === $person->getWorkingDays()) {
            $workingDays = ['1', '2', '3', '4', '5'];
        } else {
            $weeklyDays = $person->getWorkingDays();
            $workingDays = [];

            if ($weeklyDays->getMonday()) {
                $workingDays[] = '1';
            }

            if ($weeklyDays->getTuesday()) {
                $workingDays[] = '2';
            }

            if ($weeklyDays->getWednesday()) {
                $workingDays[] = '3';
            }

            if ($weeklyDays->getThursday()) {
                $workingDays[] = '4';
            }

            if ($weeklyDays->getFriday()) {
                $workingDays[] = '5';
            }

            if ($weeklyDays->getSaturday()) {
                $workingDays[] = '6';
            }

            if ($weeklyDays->getSunday()) {
                $workingDays[] = '7';
            }
        }

        return $workingDays;
    }
}
