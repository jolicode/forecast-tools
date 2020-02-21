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

use App\Entity\PublicForecast;
use App\Forecast\Builder;
use App\Repository\ForecastAccountRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function index(AuthorizationCheckerInterface $authChecker, ForecastAccountRepository $forecastAccountRepository, UserRepository $userRepository)
    {
        if (true === $authChecker->isGranted('ROLE_USER')) {
            $user = $userRepository->findOneBy(['email' => $this->getUser()->getUsername()]);
            $forecastAccounts = $forecastAccountRepository->findForecastAccountsForUser($user);

            return new RedirectResponse(
                $this->generateUrl('organization_homepage', [
                    'slug' => $forecastAccounts[0]->getSlug(),
                ]),
                Response::HTTP_TEMPORARY_REDIRECT
            );
        }

        return $this->render('home/index.html.twig');
    }

    /**
     * @Route("/forecast/{token}", name="public_forecast")
     * @Route("/forecast/{token}/{start}/{end}", name="public_forecast_start_end")
     *
     * @param mixed|null $start
     * @param mixed|null $end
     */
    public function forecast(Builder $forecastBuilder, PublicForecast $publicForecast, $start = null, $end = null)
    {
        if (null === $start) {
            $start = new \DateTime('first day of this month');
        } else {
            $start = new \DateTime($start);
        }

        if (null === $end) {
            $end = new \DateTime('last day of this month');
        } else {
            $end = new \DateTime($end);
        }

        if ($start >= $end) {
            throw new \DomainException('Please have the end date be after the start date.');
        }

        $assignments = $forecastBuilder->buildAssignments($publicForecast, $start, $end);
        list($days, $weeks, $months) = $forecastBuilder->buildDays($start, $end);

        return $this->render('home/public-forecast.html.twig', [
            'assignments' => $assignments,
            'days' => $days,
            'months' => $months,
            'weeks' => $weeks,
            'start' => $start,
            'end' => $end,
            'today' => (new \DateTime())->format('Y-m-d'),
            'publicForecast' => $publicForecast,
        ]);
    }

    public function loginInfo(ForecastAccountRepository $forecastAccountRepository, RequestStack $requestStack)
    {
        $forecastAccounts = $forecastAccountRepository->findForecastAccountsForEmail($this->getUser()->getUsername());
        $request = $requestStack->getMasterRequest();

        return $this->render('home/loginInfo.html.twig', [
            'forecastAccounts' => $forecastAccounts,
            'currentAccount' => $request->attributes->get('forecastAccount'),
        ]);
    }
}
