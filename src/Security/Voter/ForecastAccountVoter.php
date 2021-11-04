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
    public const ADMIN = 'admin';
    public const HARVEST_ADMIN = 'harvest_admin';

    private $userForecastAccountRepository;
    private $userHarvestAccountRepository;

    public function __construct(UserForecastAccountRepository $userForecastAccountRepository, UserHarvestAccountRepository $userHarvestAccountRepository)
    {
        $this->userForecastAccountRepository = $userForecastAccountRepository;
        $this->userHarvestAccountRepository = $userHarvestAccountRepository;
    }

    protected function supports($attribute, $subject)
    {
        return \in_array($attribute, [self::ADMIN, self::HARVEST_ADMIN], true)
            && $subject instanceof \App\Entity\ForecastAccount;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
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

                return $userForecastAccount && $userForecastAccount->getIsAdmin();
                break;
            case self::HARVEST_ADMIN:
                if (!$forecastAccount->getHarvestAccount()) {
                    return false;
                }

                $userHarvestAccount = $this->userHarvestAccountRepository->findOneByEmailAndForecastAccount($user->getUserIdentifier(), $forecastAccount->getHarvestAccount());

                return $userHarvestAccount && $userHarvestAccount->getIsAdmin();
                break;
        }

        return false;
    }
}
