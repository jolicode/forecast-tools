<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security\UserProvider;

use KnpU\OAuth2ClientBundle\Security\User\OAuthUser;
use KnpU\OAuth2ClientBundle\Security\User\OAuthUserProvider as UserOAuthUserProvider;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;

class OAuthUserProvider extends UserOAuthUserProvider
{
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof OAuthUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        return new OAuthUser($user->getUsername(), $user->getRoles());
    }
}
