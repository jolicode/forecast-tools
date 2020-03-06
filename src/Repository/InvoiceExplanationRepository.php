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
}
