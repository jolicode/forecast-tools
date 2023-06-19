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

use App\Entity\StandupMeetingReminder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StandupMeetingReminder>
 *
 * @method StandupMeetingReminder|null find($id, $lockMode = null, $lockVersion = null)
 * @method StandupMeetingReminder|null findOneBy(array $criteria, array $orderBy = null)
 * @method StandupMeetingReminder[]    findAll()
 * @method StandupMeetingReminder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StandupMeetingReminderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StandupMeetingReminder::class);
    }

    /**
     * @return StandupMeetingReminder[]
     */
    public function findByTime(string $time): array
    {
        return $this->createQueryBuilder('smr')
            ->select('smr')
            ->andWhere('smr.time = :time')
            ->setParameter('time', $time)
            ->getQuery()
            ->getResult()
        ;
    }
}
