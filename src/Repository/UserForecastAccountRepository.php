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
use App\Entity\User;
use App\Entity\UserForecastAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserForecastAccount>
 *
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

    /**
     * @return ForecastAccount[]
     */
    public function findForecastAccountsWithoutOtherAdmin(User $user): array
    {
        $qb = $this->createQueryBuilder('uf');

        return $this->_em->createQueryBuilder()
            ->from('App:ForecastAccount', 'fa')
            ->innerJoin('fa.userForecastAccounts', 'uf')
            ->select('fa')
            ->andWhere('uf.isAdmin = :isAdmin')
            ->andWhere(
                $qb->expr()->in(
                    'uf.forecastAccount',
                    $this->_em->createQueryBuilder()
                        ->select('IDENTITY(ufa.forecastAccount)')
                        ->from('App:UserForecastAccount', 'ufa')
                        ->innerJoin('ufa.forecastAccount', 'faa')
                        ->where('ufa.user = :user')
                        ->getDQL()
                )
            )
            ->groupBy('uf.forecastAccount')
            ->having('COUNT(uf.id) = 1')
            ->setParameter('isAdmin', true)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult()
        ;
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
