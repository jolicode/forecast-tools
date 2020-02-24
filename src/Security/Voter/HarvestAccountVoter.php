<?php

namespace App\Security\Voter;

use App\Repository\UserHarvestAccountRepository;
use App\Entity\HarvestAccount;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class HarvestAccountVoter extends Voter
{
    const ADMIN = 'admin';

    private $userHarvestAccountRepository;

    public function __construct(UserHarvestAccountRepository $userHarvestAccountRepository)
    {
        $this->userHarvestAccountRepository = $userHarvestAccountRepository;
    }

    protected function supports($attribute, $subject)
    {
        return in_array($attribute, [self::ADMIN])
            && $subject instanceof \App\Entity\HarvestAccount;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
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
                $userHarvestAccount = $this->userHarvestAccountRepository->findOneByEmailAndForecastAccount($user->getUsername(), $harvestAccount);

                return ($userHarvestAccount && $userHarvestAccount->getIsAdmin());
                break;
        }

        return false;
    }
}
