<?php

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

    public function addDesc(ViolationContainer $desc): void
    {
        $this->descViolationContainers[] = $desc;
    }

    public function all(): array
    {
        return $this->violations;
    }

    public function count(bool $withDesc = true): int
    {
        $count = count($this->violations);

        if ($withDesc) {
            foreach ($this->descViolationContainers as $desc) {
                $count += $desc->count(true);
            }
        }

        return $count;
    }

    public function hasViolations(bool $withDesc = true): bool
    {
        return ($this->count($withDesc) > 0);
    }
}
