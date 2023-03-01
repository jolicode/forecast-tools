<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\ForecastAccount;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/{slug}', name: 'organization_')]
class OrganizationController extends AbstractController
{
    #[Route(path: '/', name: 'homepage')]
    public function homepage(ForecastAccount $forecastAccount): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('organization/homepage.html.twig', [
            'forecastAccount' => $forecastAccount,
        ]);
    }
}
