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

use App\Entity\ForecastAlert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method ForecastAlert|null find($id, $lockMode = null, $lockVersion = null)
 * @method ForecastAlert|null findOneBy(array $criteria, array $orderBy = null)
 * @method ForecastAlert[]    findAll()
 * @method ForecastAlert[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ForecastAlertRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ForecastAlert::class);
    }

    public function findForUser(UserInterface $user)
    {
        return $this->createQueryBuilder('f')
            ->select('f', 'a')
            ->leftJoin('f.forecastAccount', 'a')
            ->leftJoin('a.users', 'u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $user->getUsername())
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return ForecastAlert[] Returns an array of ForecastAlert objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ForecastAlert
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
