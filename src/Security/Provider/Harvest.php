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

use League\OAuth2\Client\Token\AccessToken;
use Nilesuan\OAuth2\Client\Provider\Harvest as BaseHarvestProvider;

class Harvest extends BaseHarvestProvider
{
    public $domain = 'https://id.getharvest.com';

    public function getBaseAuthorizationUrl()
    {
        return $this->domain . '/oauth2/authorize';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->domain . '/api/v2/oauth2/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->domain . '/api/v2/accounts';
    }
}
