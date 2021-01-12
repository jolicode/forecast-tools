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

use App\Entity\InvoicingProcess;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method InvoicingProcess|null find($id, $lockMode = null, $lockVersion = null)
 * @method InvoicingProcess|null findOneBy(array $criteria, array $orderBy = null)
 * @method InvoicingProcess[]    findAll()
 * @method InvoicingProcess[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvoicingProcessRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InvoicingProcess::class);
    }

    public function findOverlapping(InvoicingProcess $invoicingProcess)
    {
        return $this->createQueryBuilder('ip')
            ->andWhere('ip.billingPeriodStart < :endDate AND ip.billingPeriodEnd > :startDate')
            ->andWhere('ip.forecastAccount = :forecastAccount')
            ->setParameter('startDate', $invoicingProcess->getBillingPeriodStart())
            ->setParameter('endDate', $invoicingProcess->getBillingPeriodEnd())
            ->setParameter('forecastAccount', $invoicingProcess->getForecastAccount())
            ->getQuery()
            ->execute()
        ;
    }
}
