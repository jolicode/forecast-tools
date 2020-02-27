<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Organization;

use App\Entity\ForecastAccount;
use App\Entity\PublicForecast;
use App\Form\PublicForecastType;
use App\Repository\PublicForecastRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/{slug}/public-forecasts", name="organization_public_forecasts_", defaults={"menu": "public-forecasts"})
 */
class PublicForecastController extends AbstractController
{
    /**
     * @Route("/", name="list")
     */
    public function publicForecasts(ForecastAccount $forecastAccount, PublicForecastRepository $publicForecastRepository)
    {
        $publicForecasts = $publicForecastRepository->findByForecastAccount($forecastAccount);

        return $this->render('organization/public_forecast/list.html.twig', [
            'publicForecasts' => $publicForecasts,
            'forecastAccount' => $forecastAccount,
        ]);
    }

    /**
     * @Route("/create", name="create")
     * @IsGranted("admin", subject="forecastAccount")
     */
    public function create(Request $request, ForecastAccount $forecastAccount, UserRepository $userRepository, EntityManagerInterface $em)
    {
        $publicForecast = new PublicForecast();
        $publicForecast->setToken(bin2hex(random_bytes(120)));
        $user = $userRepository->findOneBy(['email' => $this->getUser()->getUsername()]);
        $publicForecast->setCreatedBy($user);
        $publicForecast->setForecastAccount($forecastAccount);
        $form = $this->createForm(PublicForecastType::class, $publicForecast);
        $form->add('save', SubmitType::class, ['label' => 'Save public forecast', 'attr' => ['class' => 'btn btn-primary']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $publicForecast = $form->getData();
            $em->persist($publicForecast);
            $em->flush();

            return $this->redirectToRoute('organization_public_forecasts_list', ['slug' => $forecastAccount->getSlug()]);
        }

        return $this->render('organization/public_forecast/create.html.twig', [
            'forecastAccount' => $forecastAccount,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/edit/{publicForecastId}", name="edit")
     * @IsGranted("admin", subject="forecastAccount")
     */
    public function edit(Request $request, ForecastAccount $forecastAccount, PublicForecast $publicForecast, EntityManagerInterface $em)
    {
        $form = $this->createForm(PublicForecastType::class, $publicForecast);
        $form->add('save', SubmitType::class, ['label' => 'Save public forecast', 'attr' => ['class' => 'btn btn-primary']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $publicForecast = $form->getData();
            $em->persist($publicForecast);
            $em->flush();

            return $this->redirectToRoute('organization_public_forecasts_list', ['slug' => $forecastAccount->getSlug()]);
        }

        return $this->render('organization/public_forecast/edit.html.twig', [
            'forecastAccount' => $forecastAccount,
            'form' => $form->createView(),
            'publicForecast' => $publicForecast,
        ]);
    }

    /**
     * @Route("/delete/{publicForecastId}", name="delete")
     * @IsGranted("admin", subject="forecastAccount")
     */
    public function delete(Request $request, ForecastAccount $forecastAccount, PublicForecast $publicForecast, EntityManagerInterface $em)
    {
        $form = $this->createFormBuilder(null)
            ->setAction($this->generateUrl('organization_public_forecasts_delete', ['slug' => $forecastAccount->getSlug(), 'publicForecastId' => $publicForecast->getId()]))
            ->add('submit', SubmitType::class, ['label' => 'Yes, delete this forecast', 'attr' => ['class' => 'btn btn-danger']])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->remove($publicForecast);
            $em->flush();
            $this->addFlash(
                'success',
                '👋 This forecast has been deleted!'
            );

            return $this->redirectToRoute('homepage');
        }

        return $this->render('organization/public_forecast/delete.html.twig', [
            'forecastAccount' => $forecastAccount,
            'form' => $form->createView(),
            'publicForecast' => $publicForecast,
        ]);
    }
}
