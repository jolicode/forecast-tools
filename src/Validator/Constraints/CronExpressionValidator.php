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

use Cron\CronExpression as BaseCronExpression;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class CronExpressionValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof CronExpression) {
            throw new UnexpectedTypeException($constraint, CronExpression::class);
        }

        if (null === $value) {
            return;
        }

        if (!BaseCronExpression::isValidExpression($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->setCode(CronExpression::IS_NOT_VALID)
                ->addViolation();

            return;
        }
    }
}
