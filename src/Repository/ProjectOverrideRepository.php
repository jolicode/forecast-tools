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

use App\Entity\ProjectOverride;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectOverride>
 *
 * @method ProjectOverride|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectOverride|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectOverride[]    findAll()
 * @method ProjectOverride[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectOverrideRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectOverride::class);
    }
}
