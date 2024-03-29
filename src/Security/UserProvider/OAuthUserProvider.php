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

use App\Security\User\OAuthUser;
use KnpU\OAuth2ClientBundle\Security\User\OAuthUser as BaseOAuthUser;
use KnpU\OAuth2ClientBundle\Security\User\OAuthUserProvider as BaseOAuthUserProvider;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;

class OAuthUserProvider extends BaseOAuthUserProvider
{
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof OAuthUser && !$user instanceof BaseOAuthUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        return new OAuthUser($user->getUsername(), $user->getRoles());
    }

    /**
     * @param string $class
     */
    public function supportsClass($class): bool
    {
        return OAuthUser::class === $class || BaseOAuthUser::class === $class;
    }
}
