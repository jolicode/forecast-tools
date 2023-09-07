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
use App\Entity\ForecastReminder;
use App\Form\ForecastReminderType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/{slug}/reminder', name: 'organization_reminder_', defaults: ['menu' => 'reminder'])]
class ReminderController extends AbstractController
{
    #[Route(path: '/', name: 'index')]
    public function index(Request $request, ForecastAccount $forecastAccount, UserRepository $userRepository, EntityManagerInterface $em): \Symfony\Component\HttpFoundation\Response
    {
        $forecastReminder = $forecastAccount->getForecastReminder();

        if (null === $forecastReminder) {
            $forecastReminder = new ForecastReminder();
            $forecastReminder->setForecastAccount($forecastAccount);
            $forecastAccount->setForecastReminder($forecastReminder);
        }

        $form = $this->createForm(ForecastReminderType::class, $forecastReminder, [
            'hasSlackTeams' => \count($forecastAccount->getForecastAccountSlackTeams()) > 0,
        ]);
        $form->add('save', SubmitType::class, ['label' => 'Save this reminder', 'attr' => ['class' => 'btn btn-primary']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $forecastReminder = $form->getData();
            $forecastReminder->setUpdatedAt(new \DateTime());
            $user = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
            $forecastReminder->setUpdatedBy($user);

            foreach ($forecastReminder->getProjectOverrides() as $projectOverride) {
                if (null === $projectOverride->getCreatedBy()) {
                    $projectOverride->setCreatedBy($user);
                }
            }

            foreach ($forecastReminder->getClientOverrides() as $clientOverride) {
                if (null === $clientOverride->getCreatedBy()) {
                    $clientOverride->setCreatedBy($user);
                }
            }

            $uow = $em->getUnitOfWork();
            $uow->computeChangeSets();
            $changeset = $uow->getEntityChangeSet($forecastReminder);

            if (\count(array_diff_key($changeset, ['updatedAt' => 1, 'updatedBy' => 1])) > 0) {
                // prevent reminder modification from non-forecast admins
                // however, allow forecast simple users to edit the overrides
                $this->denyAccessUnlessGranted('admin', $forecastAccount);
            }

            $em->flush();

            return $this->redirectToRoute('organization_reminder_index', ['slug' => $forecastAccount->getSlug()]);
        }

        return $this->render('organization/reminder/index.html.twig', [
            'forecastAccount' => $forecastAccount,
            'form' => $form,
        ]);
    }
}
