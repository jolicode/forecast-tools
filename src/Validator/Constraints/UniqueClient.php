<?php

namespace  App\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Unique;

/**
 * @Annotation
 */
class UniqueClient extends Unique
{
    public $accessor = 'getHarvestClientId';
}
