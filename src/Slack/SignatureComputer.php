<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Slack;

class SignatureComputer
{
    const VERSION = 'v0';
    private string $signingSecret;

    public function __construct(string $signingSecret)
    {
        $this->signingSecret = $signingSecret;
    }

    public function compute(string $timestamp, string $payload): string
    {
        return self::VERSION . '=' . hash_hmac('sha256', sprintf(
            '%s:%s:%s',
            self::VERSION,
            $timestamp,
            $payload
        ), $this->signingSecret);
    }
}
