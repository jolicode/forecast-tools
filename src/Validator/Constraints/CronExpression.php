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

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class CronExpression extends Constraint
{
    final public const IS_NOT_VALID = '8af4b295-bd7e-41be-a600-9cb531ad8752';

    public string $message = 'The value "{{ value }}" is not a valid cron expression.';
}
