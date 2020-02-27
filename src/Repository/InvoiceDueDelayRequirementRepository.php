<?php

namespace App\Repository;

use App\Entity\InvoiceDueDelayRequirement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method InvoiceDueDelayRequirement|null find($id, $lockMode = null, $lockVersion = null)
 * @method InvoiceDueDelayRequirement|null findOneBy(array $criteria, array $orderBy = null)
 * @method InvoiceDueDelayRequirement[]    findAll()
 * @method InvoiceDueDelayRequirement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvoiceDueDelayRequirementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InvoiceDueDelayRequirement::class);
    }

    // /**
    //  * @return InvoiceDueDelayRequirement[] Returns an array of InvoiceDueDelayRequirement objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?InvoiceDueDelayRequirement
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
