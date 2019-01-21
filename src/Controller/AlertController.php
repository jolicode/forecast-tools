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

use App\Entity\ForecastAlert;
use App\Form\ForecastAlertType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

class AlertController extends AbstractController
{
    /**
     * @Route("/alert/create", name="alert_create")
     */
    public function create(Request $request)
    {
        $alert = new ForecastAlert();
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')
            ->findOneBy(['email' => $this->getUser()->getUsername()]);
        $alert->setCreatedBy($user);
        $form = $this->createForm(ForecastAlertType::class, $alert, [
            'currentUser' => $user,
        ]);
        $form->add('save', SubmitType::class, ['label' => 'Save Alert', 'attr' => ['class' => 'btn btn-primary']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $alert = $form->getData();

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($alert);
            $entityManager->flush();

            return $this->redirectToRoute('homepage');
        }

        return $this->render('alert/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/alert/edit/{id}", name="alert_edit")
     */
    public function edit(Request $request, ForecastAlert $alert)
    {
        $user = $this->getDoctrine()->getManager()->getRepository('App:User')
            ->findOneBy(['email' => $this->getUser()->getUsername()]);

        if (!$user->hasForecastAccount($alert->getForecastAccount())) {
            throw new AccessDeniedHttpException();
        }

        $form = $this->createForm(ForecastAlertType::class, $alert, [
            'currentUser' => $user,
        ]);
        $form->add('save', SubmitType::class, ['label' => 'Save Alert', 'attr' => ['class' => 'btn btn-primary']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $alert = $form->getData();

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($alert);
            $entityManager->flush();

            return $this->redirectToRoute('homepage');
        }

        return $this->render('alert/edit.html.twig', [
            'form' => $form->createView(),
            'alert' => $alert,
        ]);
    }
}
