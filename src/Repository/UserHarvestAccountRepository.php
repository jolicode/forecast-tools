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

use App\Entity\HarvestAccount;
use App\Entity\UserHarvestAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserHarvestAccount>
 *
 * @method UserHarvestAccount|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserHarvestAccount|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserHarvestAccount[]    findAll()
 * @method UserHarvestAccount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserHarvestAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserHarvestAccount::class);
    }

    public function findOneByEmailAndForecastAccount(string $email, HarvestAccount $harvestAccount): ?UserHarvestAccount
    {
        return $this->createQueryBuilder('uh')
            ->leftJoin('uh.user', 'u')
            ->andWhere('uh.harvestAccount = :harvestAccount')
            ->andWhere('u.email = :email')
            ->setParameter('harvestAccount', $harvestAccount)
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
