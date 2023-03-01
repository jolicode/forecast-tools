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
    final public const DELAY = 7 * 86400;

    public function __construct(private readonly EntityManagerInterface $em, private readonly ClientRegistry $clientRegistry, private readonly LoggerInterface $logger, private readonly ForecastAccountRepository $forecastAccountRepository)
    {
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
                $response = json_decode($e->getResponseBody(), true, 512, \JSON_THROW_ON_ERROR);
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
