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
     * @return ForecastAccount[] Returns an array of ForecastAccount objects
     */
    public function findBySlackTeamId(string $teamId)
    {
        return $this->createQueryBuilder('f')
            ->select('f')
            ->leftJoin('f.forecastAccountSlackTeams', 'fast')
            ->leftJoin('fast.slackTeam', 's')
            ->andWhere('s.teamId = :teamId')
            ->setParameter('teamId', $teamId)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
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

    /**
     * @return ForecastAccount[] Returns an array of ForecastAccount objects
     */
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
            ->getResult()
        ;
    }

    /**
     * @param mixed $user
     *
     * @return ForecastAccount[] Returns an array of ForecastAccount objects
     */
    public function findForecastAccountsForUser($user)
    {
        return $this->findForecastAccountsForEmail($user->getEmail());
    }
}
