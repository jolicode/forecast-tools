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

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    #[Route(path: '/connect', name: 'connect_harvest')]
    public function connect(ClientRegistry $clientRegistry): \Symfony\Component\HttpFoundation\Response
    {
        return $clientRegistry
            ->getClient('harvest')
            ->redirect(['all'], []);
    }

    #[Route(path: '/connect/check', name: 'connect_harvest_check')]
    public function connectCheck(): void
    {
    }
}
