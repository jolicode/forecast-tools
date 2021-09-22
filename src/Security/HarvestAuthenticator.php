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
use App\Entity\HarvestAccount;
use App\Entity\User;
use App\Entity\UserForecastAccount;
use App\Entity\UserHarvestAccount;
use App\Exception\RedirectException;
use App\Repository\ForecastAccountRepository;
use App\Repository\HarvestAccountRepository;
use App\Repository\UserForecastAccountRepository;
use App\Repository\UserHarvestAccountRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use JoliCode\Forecast\ClientFactory as ForecastClientFactory;
use JoliCode\Harvest\ClientFactory as HarvestClientFactory;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
use KnpU\OAuth2ClientBundle\Security\User\OAuthUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class HarvestAuthenticator extends SocialAuthenticator
{
    private $clientRegistry;
    private $em;
    private $forecastAccountRepository;
    private $harvestAccountRepository;
    private $urlGenerator;
    private $userForecastAccountRepository;
    private $userHarvestAccountRepository;
    private $userRepository;
    private $harvestIdToForecastAccountRelationships = [];

    public function __construct(
        EntityManagerInterface $em,
        ClientRegistry $clientRegistry,
        UrlGeneratorInterface $urlGenerator,
        UserRepository $userRepository,
        ForecastAccountRepository $forecastAccountRepository,
        UserForecastAccountRepository $userForecastAccountRepository,
        HarvestAccountRepository $harvestAccountRepository,
        UserHarvestAccountRepository $userHarvestAccountRepository
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->em = $em;
        $this->forecastAccountRepository = $forecastAccountRepository;
        $this->harvestAccountRepository = $harvestAccountRepository;
        $this->urlGenerator = $urlGenerator;
        $this->userRepository = $userRepository;
        $this->userForecastAccountRepository = $userForecastAccountRepository;
        $this->userHarvestAccountRepository = $userHarvestAccountRepository;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new RedirectResponse(
            $this->urlGenerator->generate('connect_harvest'),
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $targetUrl = $this->getPreviousUrl($request, $providerKey);

        if (null === $targetUrl) {
            $targetUrl = $this->urlGenerator->generate('homepage');
        }

        return new RedirectResponse($targetUrl);
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
        /** @var Nilesuan\OAuth2\Client\Provider\HarvestResourceOwner $harvestUser */
        $harvestUser = $this->getHarvestClient()->fetchUserFromToken($credentials);
        $userData = $harvestUser->toArray();
        $email = $harvestUser->getEmail();
        $user = $this->userRepository->findOneBy(['email' => $email]);
        $roles = ['ROLE_USER', 'ROLE_OAUTH_USER'];

        if (!$user) {
            $user = new User();
            $this->em->persist($user);
            $user->setEmail($email);
            $user->setForecastId($userData['user']['id']);
        }

        $user->setAccessToken($credentials->getToken());
        $user->setRefreshToken($credentials->getRefreshToken());
        $user->setExpires($credentials->getExpires());
        $user->setName($harvestUser->getName());

        usort($userData['accounts'], function ($a, $b) {
            return $a['product'] > $b['product'];
        });

        $forecastAccounts = [];
        $harvestAccounts = [];

        foreach ($userData['accounts'] as $account) {
            if ('forecast' === $account['product']) {
                $forecastAccounts[] = $this->addForecastAccount($user, $account);
            } elseif ('harvest' === $account['product']) {
                $harvestAccounts[] = $this->addHarvestAccount($user, $account);
            }
        }

        $this->em->flush();
        $this->userRepository->cleanupExtraneousAccountsForUser($user, array_filter($forecastAccounts), array_filter($harvestAccounts));

        if ($user->getIsSuperAdmin()) {
            $roles[] = 'ROLE_ADMIN';
        }

        return new OAuthUser($email, $roles);
    }

    private function addForecastAccount(User $user, array $account): ?ForecastAccount
    {
        $forecastAccount = $this->forecastAccountRepository->findOneBy(['forecastId' => $account['id']]);

        if (!$forecastAccount) {
            $forecastAccount = new ForecastAccount();
            $forecastAccount->setName($account['name']);
            $forecastAccount->setForecastId($account['id']);
        }

        $client = ForecastClientFactory::create(
            $user->getAccessToken(),
            $account['id']
        );
        $currentUser = $client->whoAmI()->getCurrentUser();
        $forecastUser = $client->getPerson($currentUser->getId())->getPerson();
        $userForecastAccount = $this->userForecastAccountRepository->findOneBy([
            'forecastId' => $currentUser->getId(),
        ]);

        if (!$userForecastAccount) {
            $userForecastAccount = new UserForecastAccount();
            $userForecastAccount->setForecastId($currentUser->getId());
            $userForecastAccount->setForecastAccount($forecastAccount);
            $userForecastAccount->setUser($user);
        }

        if ($forecastUser->getHarvestUserId()) {
            $this->harvestIdToForecastAccountRelationships[$forecastUser->getHarvestUserId()] = $forecastAccount;
        }

        $userForecastAccount->setIsAdmin($forecastUser->getAdmin());
        $userForecastAccount->setIsEnabled(
            'enabled' === $forecastUser->getLogin()
                && !$forecastUser->getArchived()
        );
        $forecastAccount->setAccessToken($user->getAccessToken());
        $forecastAccount->setRefreshToken($user->getRefreshToken());
        $forecastAccount->setExpires($user->getExpires());
        $this->em->persist($forecastAccount);
        $this->em->persist($userForecastAccount);

        return $forecastAccount;
    }

    private function addHarvestAccount(User $user, array $account): ?HarvestAccount
    {
        $client = HarvestClientFactory::create(
            $user->getAccessToken(),
            $account['id']
        );
        $harvestUser = $client->retrieveTheCurrentlyAuthenticatedUser();
        $company = $client->retrieveCompany();

        if ($company instanceof \JoliCode\Harvest\Api\Model\Error) {
            // The company requires Google signin, which seems to break Harvest API...
            // redirect the user to the Harvest authentication page requiring Google auth
            throw new RedirectException(
                sprintf(
                    'https://id.getharvest.com/accounts/%s/google',
                    $account['id']
                ),
                sprintf(
                    'The "%s" harvest organization requires Google signin and the user did not signin that way.',
                    $account['name']
                )
            );
        }

        $harvestAccount = $this->harvestAccountRepository->findOneBy(['harvestId' => $account['id']]);

        if (!$harvestAccount) {
            $harvestAccount = new HarvestAccount();
            $harvestAccount->setHarvestId($account['id']);
        }

        $harvestAccount->setName($account['name']);
        $harvestAccount->setBaseUri($company->getBaseUri());
        $userHarvestAccount = $this->userHarvestAccountRepository->findOneBy([
            'harvestId' => $harvestUser->getId(),
        ]);

        if (!$userHarvestAccount) {
            $userHarvestAccount = new UserHarvestAccount();
            $userHarvestAccount->setHarvestId($harvestUser->getId());
            $userHarvestAccount->setHarvestAccount($harvestAccount);
            $userHarvestAccount->setUser($user);
        }

        $userHarvestAccount->setIsAdmin($harvestUser->getIsAdmin());
        $userHarvestAccount->setIsEnabled($harvestUser->getIsActive());
        $harvestAccount->setAccessToken($user->getAccessToken());
        $harvestAccount->setRefreshToken($user->getRefreshToken());
        $harvestAccount->setExpires($user->getExpires());

        if (isset($this->harvestIdToForecastAccountRelationships[$harvestUser->getId()])) {
            $harvestAccount->setForecastAccount(
                $this->harvestIdToForecastAccountRelationships[$harvestUser->getId()]
            );
        }

        $this->em->persist($harvestAccount);
        $this->em->persist($userHarvestAccount);

        return $harvestAccount;
    }

    private function getHarvestClient(): OAuth2Client
    {
        return $this->clientRegistry
            ->getClient('harvest')
        ;
    }
}
