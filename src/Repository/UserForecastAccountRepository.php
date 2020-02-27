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

use App\Entity\ForecastAccount;
use App\Entity\UserForecastAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method UserForecastAccount|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserForecastAccount|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserForecastAccount[]    findAll()
 * @method UserForecastAccount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserForecastAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserForecastAccount::class);
    }

    public function findOneByEmailAndForecastAccount(string $email, ForecastAccount $forecastAccount): ?UserForecastAccount
    {
        return $this->createQueryBuilder('uf')
            ->leftJoin('uf.user', 'u')
            ->andWhere('uf.forecastAccount = :forecastAccount')
            ->andWhere('u.email = :email')
            ->setParameter('forecastAccount', $forecastAccount)
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
