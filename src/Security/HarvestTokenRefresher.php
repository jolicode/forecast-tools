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
use App\Exception\HarvestIdentityProviderException;
use App\Repository\ForecastAccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use Psr\Log\LoggerInterface;

class HarvestTokenRefresher
{
    public const DELAY = 7 * 86400;
    private $clientRegistry;
    private $em;
    private $logger;
    private $forecastAccountRepository;

    public function __construct(EntityManagerInterface $em, ClientRegistry $clientRegistry, LoggerInterface $logger, ForecastAccountRepository $forecastAccountRepository)
    {
        $this->clientRegistry = $clientRegistry;
        $this->em = $em;
        $this->logger = $logger;
        $this->forecastAccountRepository = $forecastAccountRepository;
    }

    public function refresh()
    {
        $forecastAccounts = $this->forecastAccountRepository->findExpiringTokens(self::DELAY);
        $updated = 0;
        $failed = 0;

        foreach ($forecastAccounts as $forecastAccount) {
            try {
                if ('' !== $forecastAccount->getRefreshToken()) {
                    $this->refreshToken($forecastAccount);
                    ++$updated;
                }
            } catch (HarvestIdentityProviderException $e) {
                $response = json_decode($e->getResponseBody(), true);
                $response['account_id'] = $forecastAccount->getId();
                $this->logger->error(sprintf('Could not refresh token: "%s"', $e->getMessage()), $response);
                ++$failed;

                if ('invalid_grant' === $response['error']) {
                    $forecastAccount->setRefreshToken('');
                    $forecastAccount->setAccessToken('');

                    // @TODO send a mail to the customer?
                }
            } catch (\Exception $e) {
                $this->logger->error(sprintf('Could not refresh token: "%s"', $e->getMessage()), [
                    'account_id' => $forecastAccount->getId(),
                ]);
                ++$failed;
            }
        }

        $this->em->flush();

        return [$updated, $failed];
    }

    private function refreshToken(ForecastAccount $forecastAccount)
    {
        $token = $this->getHarvestClient()->getOAuth2Provider()->getAccessToken(
            'refresh_token', [
                'refresh_token' => $forecastAccount->getRefreshToken(),
            ]
        );

        $forecastAccount->setAccessToken($token->getToken());
        $forecastAccount->setRefreshToken($token->getRefreshToken());
        $forecastAccount->setExpires($token->getExpires());
        $this->em->persist($forecastAccount);
    }

    private function getHarvestClient(): OAuth2ClientInterface
    {
        return $this->clientRegistry
            ->getClient('harvest')
        ;
    }
}
