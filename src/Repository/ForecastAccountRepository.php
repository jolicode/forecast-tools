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
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ForecastAccount|null find($id, $lockMode = null, $lockVersion = null)
 * @method ForecastAccount|null findOneBy(array $criteria, array $orderBy = null)
 * @method ForecastAccount[]    findAll()
 * @method ForecastAccount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ForecastAccountRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ForecastAccount::class);
    }

    /**
     * @param mixed $delay
     *
     * @return ForecastAccount[] Returns an array of ForecastAccount objects
     */
    public function findExpiringTokens($delay)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.expires <= :expires')
            ->setParameter('expires', time() + $delay)
            ->getQuery()
            ->getResult()
        ;
    }
}
