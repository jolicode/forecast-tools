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

use App\Entity\ClientOverride;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ClientOverride|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClientOverride|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClientOverride[]    findAll()
 * @method ClientOverride[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientOverrideRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientOverride::class);
    }
}
