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

use App\Entity\HarvestAccount;
use App\Repository\UserHarvestAccountRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class HarvestAccountVoter extends Voter
{
    final public const ADMIN = 'admin';

    public function __construct(private readonly UserHarvestAccountRepository $userHarvestAccountRepository)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::ADMIN], true)
            && $subject instanceof \App\Entity\HarvestAccount;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var HarvestAccount $harvestAccount */
        $harvestAccount = $subject;

        switch ($attribute) {
            case self::ADMIN:
                $userHarvestAccount = $this->userHarvestAccountRepository->findOneByEmailAndForecastAccount($user->getUserIdentifier(), $harvestAccount);

                return null !== $userHarvestAccount ? $userHarvestAccount->getIsAdmin() : false;
        }

        return false;
    }
}
