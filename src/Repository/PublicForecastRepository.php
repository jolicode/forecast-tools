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
use App\Entity\PublicForecast;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method PublicForecast|null find($id, $lockMode = null, $lockVersion = null)
 * @method PublicForecast|null findOneBy(array $criteria, array $orderBy = null)
 * @method PublicForecast[]    findAll()
 * @method PublicForecast[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PublicForecastRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PublicForecast::class);
    }

    public function findForAccountAndUser(ForecastAccount $forecastAccount, UserInterface $user)
    {
        return $this->createQueryBuilder('p')
            ->select('p', 'f')
            ->leftJoin('p.forecastAccount', 'f')
            ->leftJoin('f.userForecastAccounts', 'ufa')
            ->leftJoin('ufa.user', 'u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $user->getUsername())
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return PublicForecast[] Returns an array of PublicForecast objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?PublicForecast
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
