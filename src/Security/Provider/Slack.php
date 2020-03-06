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

use AdamPaterson\OAuth2\Client\Provider\Slack as BaseSlack;

class Slack extends BaseSlack
{
    public function getBaseAuthorizationUrl()
    {
        return 'https://slack.com/oauth/v2/authorize';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return 'https://slack.com/api/oauth.v2.access';
    }
}
