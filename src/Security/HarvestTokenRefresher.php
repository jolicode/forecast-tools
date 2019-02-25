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
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use Psr\Log\LoggerInterface;

class HarvestTokenRefresher
{
    const DELAY = 7 * 86400;
    private $clientRegistry;
    private $em;
    private $logger;

    public function __construct(EntityManagerInterface $em, ClientRegistry $clientRegistry, LoggerInterface $logger)
    {
        $this->clientRegistry = $clientRegistry;
        $this->em = $em;
        $this->logger = $logger;
    }

    public function refresh()
    {
        $forecastAccounts = $this->em->getRepository('App:ForecastAccount')->findExpiringTokens(self::DELAY);
        $updated = 0;
        $failed = 0;

        foreach ($forecastAccounts as $forecastAccount) {
            try {
                $this->refreshToken($forecastAccount);
                ++$updated;
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

    private function getHarvestClient(): OAuth2Client
    {
        return $this->clientRegistry
            ->getClient('harvest')
        ;
    }
}
