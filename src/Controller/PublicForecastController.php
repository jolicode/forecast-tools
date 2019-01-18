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
use App\Form\PublicForecastType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PublicForecastController extends AbstractController
{
    /**
     * @Route("/forecast/{token}", name="public_forecast")
     * @Route("/forecast/{token}/{start}/{end}", name="public_forecast_start_end")
     *
     * @param mixed|null $start
     * @param mixed|null $end
     */
    public function index(Builder $forecastBuilder, PublicForecast $publicForecast, $start = null, $end = null)
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

        return $this->render('public_forecast/show.html.twig', [
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

    /**
     * @Route("/public-forecast/create", name="public_forecast_create")
     */
    public function create(Request $request)
    {
        $publicForecast = new PublicForecast();
        $publicForecast->setToken(bin2hex(random_bytes(120)));

        $user = $this->getDoctrine()->getManager()->getRepository('App:User')
            ->findOneBy(['email' => $this->getUser()->getUsername()]);
        $publicForecast->setCreatedBy($user);
        $form = $this->createForm(PublicForecastType::class, $publicForecast);
        $form->add('save', SubmitType::class, ['label' => 'Save public forecast', 'attr' => ['class' => 'btn btn-primary']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $publicForecast = $form->getData();

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($publicForecast);
            $entityManager->flush();

            return $this->redirectToRoute('homepage');
        }

        return $this->render('public_forecast/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/public-forecast/edit/{id}", name="public_forecast_edit")
     */
    public function edit(Request $request, PublicForecast $publicForecast)
    {
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')
            ->findOneBy(['email' => $this->getUser()->getUsername()]);

        if (!$user->hasForecastAccount($publicForecast->getForecastAccount())) {
            throw new AccessDeniedHttpException();
        }

        $form = $this->createForm(PublicForecastType::class, $publicForecast);
        $form->add('save', SubmitType::class, ['label' => 'Save public forecast', 'attr' => ['class' => 'btn btn-primary']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $publicForecast = $form->getData();

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($publicForecast);
            $entityManager->flush();

            return $this->redirectToRoute('homepage');
        }

        return $this->render('public_forecast/edit.html.twig', [
            'form' => $form->createView(),
            'publicForecast' => $publicForecast,
        ]);
    }
}
