<?php

namespace App\Repository;

use App\Entity\InvoiceExplanation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method InvoiceExplanation|null find($id, $lockMode = null, $lockVersion = null)
 * @method InvoiceExplanation|null findOneBy(array $criteria, array $orderBy = null)
 * @method InvoiceExplanation[]    findAll()
 * @method InvoiceExplanation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvoiceExplanationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InvoiceExplanation::class);
    }

    // /**
    //  * @return InvoiceExplanation[] Returns an array of InvoiceExplanation objects
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
    public function findOneBySomeField($value): ?InvoiceExplanation
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
