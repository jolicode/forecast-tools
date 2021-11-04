<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security\User;

use KnpU\OAuth2ClientBundle\Security\User\OAuthUser as BaseOAuthUser;

class OAuthUser extends BaseOAuthUser
{
    public function getUserIdentifier(): string
    {
        return $this->getUsername();
    }
}
