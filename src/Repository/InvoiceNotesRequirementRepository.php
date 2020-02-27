<?php

namespace App\Repository;

use App\Entity\InvoiceNotesRequirement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method InvoiceNotesRequirement|null find($id, $lockMode = null, $lockVersion = null)
 * @method InvoiceNotesRequirement|null findOneBy(array $criteria, array $orderBy = null)
 * @method InvoiceNotesRequirement[]    findAll()
 * @method InvoiceNotesRequirement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvoiceNotesRequirementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InvoiceNotesRequirement::class);
    }

    // /**
    //  * @return InvoiceNotesRequirement[] Returns an array of InvoiceNotesRequirement objects
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
    public function findOneBySomeField($value): ?InvoiceNotesRequirement
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
