<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security\Provider;

use App\Exception\HarvestIdentityProviderException;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Harvest extends AbstractProvider
{
    use BearerAuthorizationTrait;

    public string $domain = 'https://id.getharvest.com';

    public function getBaseAuthorizationUrl()
    {
        return $this->domain . '/oauth2/authorize';
    }

    /**
     * @param array<string, mixed> $params
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->domain . '/api/v2/oauth2/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->domain . '/api/v2/accounts';
    }

    /**
     * @param array<string, mixed> $options
     */
    public function getAuthenticatedRequest($method, $url, $token, array $options = [])
    {
        $options['headers'] = ['Content-Type' => 'application/json', 'Accept' => 'application/json'];

        return $this->createRequest($method, $url, $token, $options);
    }

    /**
     * @return array<array-key, string>
     */
    protected function getDefaultScopes()
    {
        return [];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400) {
            throw HarvestIdentityProviderException::clientException($response, $data);
        } elseif (isset($data['error'])) {
            throw HarvestIdentityProviderException::oauthException($response, $data);
        }
    }

    /**
     * @param array<string, mixed> $response
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        $user = new HarvestResourceOwner($response);

        return $user->setDomain($this->domain);
    }
}
