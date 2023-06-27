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

use App\Entity\PublicForecast;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PublicForecast>
 *
 * @method PublicForecast|null find($id, $lockMode = null, $lockVersion = null)
 * @method PublicForecast|null findOneBy(array $criteria, array $orderBy = null)
 * @method PublicForecast|null findOneByToken(string $value)
 * @method PublicForecast[]    findAll()
 * @method PublicForecast[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PublicForecastRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PublicForecast::class);
    }
}
