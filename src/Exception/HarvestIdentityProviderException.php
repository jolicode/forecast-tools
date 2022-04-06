<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Exception;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Message\ResponseInterface;

class HarvestIdentityProviderException extends IdentityProviderException
{
    /**
     * Creates client exception from response.
     *
     * @param array|string $data Parsed response data
     */
    public static function clientException(ResponseInterface $response, $data): self
    {
        return static::fromResponse(
            $response,
            $data['message'] ?? $response->getReasonPhrase()
        );
    }

    /**
     * Creates oauth exception from response.
     *
     * @param array|string $data Parsed response data
     */
    public static function oauthException(ResponseInterface $response, $data): self
    {
        return static::fromResponse(
            $response,
            $data['error'] ?? $response->getReasonPhrase()
        );
    }

    /**
     * Creates identity exception from response.
     *
     * @param string $message
     */
    protected static function fromResponse(ResponseInterface $response, $message = null): self
    {
        return new self($message, $response->getStatusCode(), (string) $response->getBody());
    }
}
