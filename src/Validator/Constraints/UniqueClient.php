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

use Symfony\Component\Validator\Constraints\Unique;

/**
 * @Annotation
 */
class UniqueClient extends Unique
{
    public $accessor = 'getHarvestClientId';
}
