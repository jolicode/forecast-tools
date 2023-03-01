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
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Event;
use Eluceo\iCal\Domain\ValueObject\Date;
use Eluceo\iCal\Domain\ValueObject\SingleDay;
use Eluceo\iCal\Domain\ValueObject\UniqueIdentifier;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class HomeController extends AbstractController
{
    #[Route(path: '/', name: 'homepage')]
    public function index(AuthorizationCheckerInterface $authChecker, ForecastAccountRepository $forecastAccountRepository, UserRepository $userRepository): Response
    {
        if (true === $authChecker->isGranted('ROLE_USER')) {
            $user = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
            $defaultForecastAccount = $user->getDefaultForecastAccount();

            if (null === $defaultForecastAccount) {
                $forecastAccounts = $forecastAccountRepository->findForecastAccountsForUser($user);
                $defaultForecastAccount = $forecastAccounts[0] ?? null;
            }

            if (null === $defaultForecastAccount) {
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

    #[Route(path: '/forecast/{token}.ical', name: 'public_forecast_ical')]
    public function forecastIcal(Builder $forecastBuilder, PublicForecast $publicForecast, SluggerInterface $asciiSlugger): Response
    {
        $start = new \DateTime('-6 months');
        $end = new \DateTime('+6 months');
        $assignments = $forecastBuilder->buildAssignments($publicForecast, $start, $end);
        $calendar = new Calendar();
        $calendarFactory = new CalendarFactory();

        foreach ($assignments['total']['users'] as $user) {
            $username = $user['name'];

            foreach ($user['days'] as $day => $duration) {
                $event = (new Event(new UniqueIdentifier(sprintf(
                    'forecast-tools/events/%s-%s',
                    $asciiSlugger->slug($username),
                    $day
                ))))
                    ->setSummary($username)
                    ->setOccurrence(new SingleDay(
                        new Date(\DateTimeImmutable::createFromFormat('Y-m-d', $day))
                    ))
                ;

                if ($duration < 1) {
                    $event->setDescription(sprintf('%s day', $duration));
                }

                $calendar->addEvent($event);
            }
        }

        return new Response(
            $calendarFactory->createCalendar($calendar),
            Response::HTTP_OK,
            [
                'content-type' => 'text/calendar; charset=utf-8',
                'content-disposition' => sprintf('attachment; filename="%s.ics"', $asciiSlugger->slug($publicForecast->getName())),
            ]
        );
    }

    /**
     * @param mixed|null $start
     * @param mixed|null $end
     */
    #[Route(path: '/forecast/{token}', name: 'public_forecast')]
    #[Route(path: '/forecast/{token}/{start}/{end}', name: 'public_forecast_start_end')]
    public function forecast(Builder $forecastBuilder, PublicForecast $publicForecast, $start = null, $end = null): Response
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
        [$days, $weeks, $months] = $forecastBuilder->buildDays($start, $end);

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

    #[Route(path: '/privacy-policy', name: 'privacy_policy')]
    public function privacy(): Response
    {
        return $this->render('home/privacy-policy.html.twig');
    }

    #[Route(path: '/terms-of-service', name: 'terms')]
    public function terms(): Response
    {
        return $this->render('home/terms.html.twig');
    }
}
