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
use App\Forecast\UniqueTokenGenerator;
use App\Form\PublicForecastType;
use App\Repository\PublicForecastRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/{slug}/public-forecasts', name: 'organization_public_forecasts_', defaults: ['menu' => 'public-forecasts'])]
class PublicForecastController extends AbstractController
{
    #[Route(path: '/', name: 'list')]
    public function publicForecasts(ForecastAccount $forecastAccount, PublicForecastRepository $publicForecastRepository): Response
    {
        $publicForecasts = $publicForecastRepository->findBy([
            'forecastAccount' => $forecastAccount,
        ], [
            'name' => 'ASC',
        ]);

        return $this->render('organization/public_forecast/list.html.twig', [
            'publicForecasts' => $publicForecasts,
            'forecastAccount' => $forecastAccount,
        ]);
    }

    #[Route(path: '/create', name: 'create')]
    #[IsGranted(new Expression('is_granted("admin", subject) or subject.getAllowNonAdmins()'), subject: 'forecastAccount')]
    public function create(Request $request, ForecastAccount $forecastAccount, UserRepository $userRepository, EntityManagerInterface $em, UniqueTokenGenerator $tokenGenerator): Response
    {
        $publicForecast = new PublicForecast();
        $publicForecast->setToken($tokenGenerator->generate());
        $user = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $publicForecast->setCreatedBy($user);
        $publicForecast->setForecastAccount($forecastAccount);
        $form = $this->createForm(PublicForecastType::class, $publicForecast);
        $form->add('save', SubmitType::class, ['label' => 'Save public forecast', 'attr' => ['class' => 'btn btn-primary']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $publicForecast = $form->getData();
            $em->persist($publicForecast);
            $em->flush();

            $url = $this->generateUrl('organization_public_forecasts_edit', ['slug' => $forecastAccount->getSlug(), 'publicForecastId' => $publicForecast->getId()]);
            $this->addFlash(
                'success',
                sprintf('👋 This public forecast has been successfully created! <a href="%s">Review its details</a>', $url)
            );

            return $this->redirectToRoute('organization_public_forecasts_list', ['slug' => $forecastAccount->getSlug()]);
        }

        return $this->render('organization/public_forecast/create.html.twig', [
            'forecastAccount' => $forecastAccount,
            'form' => $form,
        ]);
    }

    #[Route(path: '/edit/{publicForecastId}', name: 'edit')]
    #[IsGranted(new Expression('is_granted("admin", subject) or subject.getAllowNonAdmins()'), subject: 'forecastAccount')]
    public function edit(
        Request $request,
        ForecastAccount $forecastAccount,
        #[MapEntity(id: 'publicForecastId')] PublicForecast $publicForecast,
        EntityManagerInterface $em): Response
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
            'form' => $form,
            'publicForecast' => $publicForecast,
        ]);
    }

    #[Route(path: '/delete/{publicForecastId}', name: 'delete')]
    #[IsGranted(new Expression('is_granted("admin", subject) or subject.getAllowNonAdmins()'), subject: 'forecastAccount')]
    public function delete(
        Request $request,
        ForecastAccount $forecastAccount,
        #[MapEntity(id: 'publicForecastId')] PublicForecast $publicForecast,
        EntityManagerInterface $em): Response
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

            return $this->redirectToRoute('organization_public_forecasts_list', ['slug' => $forecastAccount->getSlug()]);
        }

        return $this->render('organization/public_forecast/delete.html.twig', [
            'forecastAccount' => $forecastAccount,
            'form' => $form,
            'publicForecast' => $publicForecast,
        ]);
    }
}
