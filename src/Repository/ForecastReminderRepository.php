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

use App\Entity\ForecastReminder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ForecastReminder|null find($id, $lockMode = null, $lockVersion = null)
 * @method ForecastReminder|null findOneBy(array $criteria, array $orderBy = null)
 * @method ForecastReminder[]    findAll()
 * @method ForecastReminder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ForecastReminderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForecastReminder::class);
    }

    public function findByIdAndTeamId(string $id, string $teamId)
    {
        return $this->createQueryBuilder('fr')
            ->select('fr')
            ->leftJoin('fr.forecastAccount', 'fa')
            ->leftJoin('fa.slackChannels', 'sc')
            ->andWhere('fr.id = :id')
            ->andWhere('sc.teamId = :teamId')
            ->setParameter('id', $id)
            ->setParameter('teamId', $teamId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByTeamId(string $teamId)
    {
        return $this->createQueryBuilder('fr')
            ->select('fr')
            ->leftJoin('fr.forecastAccount', 'fa')
            ->leftJoin('fa.slackChannels', 'sc')
            ->andWhere('sc.teamId = :teamId')
            ->setParameter('teamId', $teamId)
            ->addOrderBy('fa.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return ForecastReminder[] Returns an array of ForecastReminder objects
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
    public function findOneBySomeField($value): ?ForecastReminder
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
