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
    private const CHARS_ALPHABET = '0123456789aAbBcCdDeEfFgGhHiIjJkKlLmMnNoOpPqQrRsStTuUvVwWxXyYzZ.~-_';

    private PublicForecastRepository $repository;

    public function __construct(PublicForecastRepository $repository)
    {
        $this->repository = $repository;
    }

    public function generate(): string
    {
        $value = $this->getRandomString();

        if ($this->repository->findOneByToken($value)) {
            return $this->generate();
        }

        return $value;
    }

    private function getRandomString(int $length = 60): string
    {
        if ($length < 1) {
            throw new \RangeException('Length must be a positive integer');
        }

        $output = '';
        $max = mb_strlen(self::CHARS_ALPHABET) - 1;
        $i = 0;

        while ($i < $length) {
            $output .= self::CHARS_ALPHABET[random_int(0, $max)];
            ++$i;
        }

        if (preg_match('/^[-_.~].*$/', $output) || preg_match('/^.*[-_.~]$/', $output)) {
            return $this->getRandomString($length);
        }

        return $output;
    }
}
