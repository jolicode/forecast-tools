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
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ForecastAccount|null find($id, $lockMode = null, $lockVersion = null)
 * @method ForecastAccount|null findOneBy(array $criteria, array $orderBy = null)
 * @method ForecastAccount[]    findAll()
 * @method ForecastAccount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ForecastAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForecastAccount::class);
    }

    /**
     * @param mixed $delay
     *
     * @return ForecastAccount[] Returns an array of ForecastAccount objects
     */
    public function findExpiringTokens(int $delay)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.expires <= :expires')
            ->setParameter('expires', time() + $delay)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findForecastAccountsForEmail(string $email)
    {
        return $this->createQueryBuilder('f')
            ->select('f')
            ->leftJoin('f.userForecastAccounts', 'ufa')
            ->leftJoin('ufa.user', 'u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findForecastAccountsForUser($user)
    {
        return $this->findForecastAccountsForEmail($user->getEmail());
    }
}
