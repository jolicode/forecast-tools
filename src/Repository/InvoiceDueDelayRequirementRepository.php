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
}
