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

    public function cleanupExtraneousAccountsForUser(User $user, array $forecastAccounts, array $harvestAccounts)
    {
        $this->cleanupExtraneousForecastAccountsForUser($user, $forecastAccounts);
        $this->cleanupExtraneousHarvestAccountsForUser($user, $harvestAccounts);
    }

    public function cleanupExtraneousForecastAccountsForUser(User $user, array $forecastAccounts)
    {
        $qb = $this->createQueryBuilder('u');

        return $qb
            ->delete('App\Entity\UserForecastAccount', 'ufa')
            ->andWhere('ufa.user = :user')
            ->andWhere($qb->expr()->notIn('ufa.forecastAccount', ':forecastAccounts'))
            ->setParameter('forecastAccounts', $forecastAccounts)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function cleanupExtraneousHarvestAccountsForUser(User $user, array $harvestAccounts)
    {
        $qb = $this->createQueryBuilder('u');

        return $qb
            ->delete('App\Entity\UserHarvestAccount', 'uha')
            ->andWhere('uha.user = :user')
            ->andWhere($qb->expr()->notIn('uha.harvestAccount', ':harvestAccounts'))
            ->setParameter('harvestAccounts', $harvestAccounts)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}
