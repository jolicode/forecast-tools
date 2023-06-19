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
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HarvestAccount>
 *
 * @method HarvestAccount|null find($id, $lockMode = null, $lockVersion = null)
 * @method HarvestAccount|null findOneBy(array $criteria, array $orderBy = null)
 * @method HarvestAccount[]    findAll()
 * @method HarvestAccount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HarvestAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HarvestAccount::class);
    }

    /**
     * @return HarvestAccount[]
     */
    public function findAllHavingTimesheetReminderSlackTeam()
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.timesheetReminderSlackTeam IS NOT NULL')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return HarvestAccount[]
     */
    public function findBySlackTeamId(string $teamId)
    {
        return $this->createQueryBuilder('h')
            ->select('h')
            ->leftJoin('h.timesheetReminderSlackTeam', 'fast')
            ->leftJoin('fast.slackTeam', 's')
            ->andWhere('s.teamId = :teamId')
            ->setParameter('teamId', $teamId)
            ->getQuery()
            ->getResult();
    }
}
