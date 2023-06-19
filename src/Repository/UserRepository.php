<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User|null findOneByEmail(string $value)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @param array<\App\Entity\ForecastAccount> $forecastAccounts
     * @param array<\App\Entity\HarvestAccount>  $harvestAccounts
     */
    public function cleanupExtraneousAccountsForUser(User $user, array $forecastAccounts, array $harvestAccounts): void
    {
        $this->cleanupExtraneousForecastAccountsForUser($user, $forecastAccounts);
        $this->cleanupExtraneousHarvestAccountsForUser($user, $harvestAccounts);
    }

    /**
     * @param array<\App\Entity\ForecastAccount> $forecastAccounts
     */
    public function cleanupExtraneousForecastAccountsForUser(User $user, array $forecastAccounts): int
    {
        $qb = $this->createQueryBuilder('u');

        return $qb
            ->delete(\App\Entity\UserForecastAccount::class, 'ufa')
            ->andWhere('ufa.user = :user')
            ->andWhere($qb->expr()->notIn('ufa.forecastAccount', ':forecastAccounts'))
            ->setParameter('forecastAccounts', $forecastAccounts)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * @param array<\App\Entity\HarvestAccount> $harvestAccounts
     */
    public function cleanupExtraneousHarvestAccountsForUser(User $user, array $harvestAccounts): int
    {
        $qb = $this->createQueryBuilder('u');

        return $qb
            ->delete(\App\Entity\UserHarvestAccount::class, 'uha')
            ->andWhere('uha.user = :user')
            ->andWhere($qb->expr()->notIn('uha.harvestAccount', ':harvestAccounts'))
            ->setParameter('harvestAccounts', $harvestAccounts)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}
