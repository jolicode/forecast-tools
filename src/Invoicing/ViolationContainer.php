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

class ViolationContainer
{
    private $violations = [];
    private $descViolationContainers = [];

    public function __construct($parentViolationContainer = null)
    {
        if ($parentViolationContainer) {
            $parentViolationContainer->addDesc($this);
        }
    }

    public function add(string $value): void
    {
        $this->violations[] = $value;
    }

    public function addDesc(self $desc): void
    {
        $this->descViolationContainers[] = $desc;
    }

    public function all(): array
    {
        return $this->violations;
    }

    public function count(bool $withDesc = true): int
    {
        $count = \count($this->violations);

        if ($withDesc) {
            foreach ($this->descViolationContainers as $desc) {
                $count += $desc->count(true);
            }
        }

        return $count;
    }

    public function hasViolations(bool $withDesc = true): bool
    {
        return $this->count($withDesc) > 0;
    }
}
