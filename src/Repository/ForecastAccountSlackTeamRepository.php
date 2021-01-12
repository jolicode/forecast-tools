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

use App\Entity\ForecastAccountSlackTeam;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ForecastAccountSlackTeam|null find($id, $lockMode = null, $lockVersion = null)
 * @method ForecastAccountSlackTeam|null findOneBy(array $criteria, array $orderBy = null)
 * @method ForecastAccountSlackTeam[]    findAll()
 * @method ForecastAccountSlackTeam[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ForecastAccountSlackTeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForecastAccountSlackTeam::class);
    }

    public function remove(ForecastAccountSlackTeam $forecastAccountSlackTeam)
    {
        $slackTeam = $forecastAccountSlackTeam->getSlackTeam();
        $this->_em->remove($forecastAccountSlackTeam);

        // if this is the last slackTeam with this workspace teamId, uninstall the app from the workspace
        if (0 === \count($slackTeam->getForecastAccountSlackTeams())) {
            // @TODO once slack-php-api releases a version using jane 5
            // call https://api.slack.com/methods/apps.uninstall
            $this->_em->remove($slackTeam);
        }
    }
}
