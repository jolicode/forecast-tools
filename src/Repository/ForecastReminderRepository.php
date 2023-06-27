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
 * @extends ServiceEntityRepository<ForecastReminder>
 *
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

    public function findByIdAndTeamId(string $id, string $teamId): ?ForecastReminder
    {
        return $this->createQueryBuilder('fr')
            ->select('fr')
            ->leftJoin('fr.forecastAccount', 'fa')
            ->leftJoin('fa.forecastAccountSlackTeams', 'fast')
            ->leftJoin('fast.slackTeam', 'sc')
            ->andWhere('fr.id = :id')
            ->andWhere('sc.teamId = :teamId')
            ->setParameter('id', $id)
            ->setParameter('teamId', $teamId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return ForecastReminder[]
     */
    public function findByTeamId(string $teamId): array
    {
        return $this->createQueryBuilder('fr')
            ->select('fr')
            ->leftJoin('fr.forecastAccount', 'fa')
            ->leftJoin('fa.forecastAccountSlackTeams', 'fast')
            ->leftJoin('fast.slackTeam', 'sc')
            ->andWhere('sc.teamId = :teamId')
            ->setParameter('teamId', $teamId)
            ->addOrderBy('fa.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
