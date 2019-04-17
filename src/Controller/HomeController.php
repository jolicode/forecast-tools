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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use App\Repository\UserRepository;
use App\Repository\ForecastAlertRepository;
use App\Repository\PublicForecastRepository;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function index(AuthorizationCheckerInterface $authChecker, ForecastAlertRepository $alertRepository, PublicForecastRepository $forecastRepository)
    {
        $alerts = [];
        $forecasts = [];

        if (true === $authChecker->isGranted('ROLE_USER')) {
            $user = $this->getUser();
            $alerts = $alertRepository->findForUser($user);
            $forecasts = $forecastRepository->findForUser($user);
        }

        return $this->render('home/index.html.twig', [
            'alerts' => $alerts,
            'forecasts' => $forecasts,
        ]);
    }

    /**
     * @Route("/data/{id}", name="data")
     */
    public function data(Request $request, ForecastAccount $account)
    {
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')
            ->findOneBy(['email' => $this->getUser()->getUsername()]);

        if (!$user->hasForecastAccount($account)) {
            throw new AccessDeniedHttpException();
        }

        $client = \JoliCode\Forecast\ClientFactory::create(
            $account->getAccessToken(),
            $account->getForecastId()
        );

        $mapper = function ($item) {
            return [
                'id' => $item->getId(),
                'text' => $item->getName(),
            ];
        };

        $clients = array_map($mapper, $client->listClients()->getClients());
        $projects = array_map($mapper, $client->listProjects()->getProjects());
        $users = array_map(function ($item) {
            return [
                'id' => $item->getId(),
                'text' => $item->getFirstname() . ' ' . $item->getLastname(),
            ];
        }, $client->listPeople()->getPeople());

        return new JsonResponse([
            'clients' => $clients,
            'projects' => $projects,
            'users' => $users,
        ]);
    }
}
