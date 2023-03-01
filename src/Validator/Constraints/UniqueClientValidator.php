<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use App\DataSelector\HarvestDataSelector;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UniqueClientValidator extends ConstraintValidator
{
    public function __construct(private readonly HarvestDataSelector $harvestDataSelector)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueClient) {
            throw new UnexpectedTypeException($constraint, UniqueClient::class);
        }

        if (null === $value) {
            return;
        }

        if (!\is_array($value) && !$value instanceof \IteratorAggregate) {
            throw new UnexpectedValueException($value, 'array|IteratorAggregate');
        }

        $collectionElements = [];

        foreach ($value as $element) {
            if (\in_array(\call_user_func([$element, $constraint->accessor]), $collectionElements, true)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $this->formatValue(\call_user_func([$element, $constraint->accessor])))
                    ->setCode(UniqueClient::IS_NOT_UNIQUE)
                    ->addViolation();

                return;
            }

            $collectionElements[] = \call_user_func([$element, $constraint->accessor]);
        }
    }

    protected function formatValue(mixed $value, int $format = 0): string
    {
        $clients = $this->harvestDataSelector->getClientsById();

        if (isset($clients[$value])) {
            return $clients[$value]->getName();
        }

        return $value;
    }
}
