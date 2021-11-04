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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function index(Request $request, AuthorizationCheckerInterface $authChecker, ForecastAccountRepository $forecastAccountRepository, UserRepository $userRepository)
    {
        if (true === $authChecker->isGranted('ROLE_USER')) {
            $user = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
            $defaultForecastAccount = $user->getDefaultForecastAccount();

            if (!$defaultForecastAccount) {
                $forecastAccounts = $forecastAccountRepository->findForecastAccountsForUser($user);
                $defaultForecastAccount = $forecastAccounts[0];
            }

            if (!$defaultForecastAccount) {
                // no forecast account?
                return $this->render('home/index.html.twig', ['forecast_account_required' => true]);
            }

            return new RedirectResponse(
                $this->generateUrl('organization_homepage', [
                    'slug' => $defaultForecastAccount->getSlug(),
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
            $start = new \DateTime('first day of last month');
        } else {
            $start = new \DateTime($start);
        }

        if (null === $end) {
            $end = new \DateTime('last day of next month');
        } else {
            $end = new \DateTime($end);
        }

        if ($start >= $end) {
            return $this->render('home/public-forecast.html.twig', [
                'assignments' => [],
                'error' => 'Please have the end date be after the start date.',
                'start' => $start,
                'end' => $end,
                'today' => (new \DateTime())->format('Y-m-d'),
                'publicForecast' => $publicForecast,
            ]);
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
        $forecastAccounts = $forecastAccountRepository->findForecastAccountsForEmail($this->getUser()->getUserIdentifier());
        $request = $requestStack->getMainRequest();

        return $this->render('home/loginInfo.html.twig', [
            'forecastAccounts' => $forecastAccounts,
            'currentAccount' => $request->attributes->get('forecastAccount'),
        ]);
    }

    /**
     * @Route("/privacy-policy", name="privacy_policy")
     */
    public function privacy()
    {
        return $this->render('home/privacy-policy.html.twig');
    }

    /**
     * @Route("/terms-of-service", name="terms")
     */
    public function terms()
    {
        return $this->render('home/terms.html.twig');
    }
}
