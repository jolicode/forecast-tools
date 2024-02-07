<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Security\Voter;

use App\Entity\ForecastAccount;
use App\Repository\UserForecastAccountRepository;
use App\Repository\UserHarvestAccountRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ForecastAccountVoter extends Voter
{
    final public const ADMIN = 'admin';
    final public const HARVEST_ADMIN = 'harvest_admin';

    public function __construct(private readonly UserForecastAccountRepository $userForecastAccountRepository, private readonly UserHarvestAccountRepository $userHarvestAccountRepository)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::ADMIN, self::HARVEST_ADMIN], true)
            && $subject instanceof ForecastAccount;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var ForecastAccount $forecastAccount */
        $forecastAccount = $subject;

        switch ($attribute) {
            case self::ADMIN:
                $userForecastAccount = $this->userForecastAccountRepository->findOneByEmailAndForecastAccount($user->getUserIdentifier(), $forecastAccount);

                return null !== $userForecastAccount ? $userForecastAccount->getIsAdmin() : false;
            case self::HARVEST_ADMIN:
                if (null === $forecastAccount->getHarvestAccount()) {
                    return false;
                }

                $userHarvestAccount = $this->userHarvestAccountRepository->findOneByEmailAndForecastAccount($user->getUserIdentifier(), $forecastAccount->getHarvestAccount());

                return null !== $userHarvestAccount ? $userHarvestAccount->getIsAdmin() : false;
        }

        return false;
    }
}
