<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Forecast;

use App\Repository\PublicForecastRepository;

class UniqueTokenGenerator
{
    public function __construct(private readonly PublicForecastRepository $repository)
    {
    }

    public function generate(): string
    {
        $value = bin2hex(random_bytes(25));

        if (null !== $this->repository->findOneByToken($value)) {
            return $this->generate();
        }

        return $value;
    }
}
