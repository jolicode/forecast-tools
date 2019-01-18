<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security;

use App\Entity\ForecastAccount;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
use KnpU\OAuth2ClientBundle\Security\User\OAuthUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class HarvestAuthenticator extends SocialAuthenticator
{
    private $clientRegistry;
    private $em;
    private $urlGenerator;

    public function __construct(EntityManagerInterface $em, ClientRegistry $clientRegistry, UrlGeneratorInterface $urlGenerator)
    {
        $this->clientRegistry = $clientRegistry;
        $this->em = $em;
        $this->urlGenerator = $urlGenerator;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new RedirectResponse($this->urlGenerator->generate('connect_harvest'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return new RedirectResponse($this->urlGenerator->generate('homepage'));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return new RedirectResponse($this->urlGenerator->generate('homepage'));
    }

    public function supports(Request $request)
    {
        return 'connect_harvest_check' === $request->attributes->get('_route');
    }

    public function getCredentials(Request $request)
    {
        return $this->fetchAccessToken($this->getHarvestClient());
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $harvestUser = $this->getHarvestClient()->fetchUserFromToken($credentials);
        $userData = $harvestUser->toArray();
        $email = $harvestUser->getEmail();
        $user = $this->em->getRepository('App:User')->findOneBy(['email' => $email]);

        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setForecastId($userData['user']['id']);
        }

        $user->setAccessToken($credentials->getToken());
        $user->setRefreshToken($credentials->getRefreshToken());
        $user->setExpires($credentials->getExpires());
        $user->setName($harvestUser->getName());
        $this->em->persist($user);

        foreach ($userData['accounts'] as $account) {
            $forecastAccount = $this->em->getRepository('App:ForecastAccount')->findOneBy(['forecastId' => $account['id']]);

            if (!$forecastAccount) {
                $forecastAccount = new ForecastAccount();
                $forecastAccount->setName($account['name']);
                $forecastAccount->setForecastId($account['id']);
                $forecastAccount->addUser($user);
            }

            $forecastAccount->setAccessToken($credentials->getToken());
            $forecastAccount->setRefreshToken($credentials->getRefreshToken());
            $forecastAccount->setExpires($credentials->getExpires());
            $this->em->persist($forecastAccount);
        }

        $this->em->flush();

        return new OAuthUser($email, ['ROLE_USER']);
    }

    private function getHarvestClient(): OAuth2Client
    {
        return $this->clientRegistry
            ->getClient('harvest')
        ;
    }
}
