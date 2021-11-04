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

use App\Client\ForecastClient;
use App\Client\HarvestClient;
use App\Entity\ForecastAccount;
use App\Entity\InvoiceExplanation;
use App\Entity\InvoicingProcess;
use App\Form\InvoiceExplanationType;
use App\Form\InvoicingCreateType;
use App\Form\InvoicingProgressType;
use App\Invoicing\Manager;
use App\Repository\InvoiceExplanationRepository;
use App\Repository\InvoicingProcessRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\StateMachine;

/**
 * @Route("/{slug}/invoicing", name="organization_invoicing_", defaults={"menu": "invoicing"})
 * @IsGranted("admin", subject="forecastAccount")
 * @IsGranted("harvest_admin", subject="forecastAccount")
 */
class InvoicingController extends AbstractController
{
    private $invoicingStateMachine;
    private $em;
    private $invoicingManager;
    private $forecastClient;
    private $harvestClient;

    public function __construct(StateMachine $invoicingStateMachine, EntityManagerInterface $em, Manager $invoicingManager, ForecastClient $forecastClient, HarvestClient $harvestClient)
    {
        $this->invoicingStateMachine = $invoicingStateMachine;
        $this->em = $em;
        $this->invoicingManager = $invoicingManager;
        $this->forecastClient = $forecastClient;
        $this->harvestClient = $harvestClient;
    }

    /**
     * @Route("/", name="index")
     */
    public function index(ForecastAccount $forecastAccount, InvoicingProcessRepository $invoicingProcessRepository)
    {
        $invoicingProcesses = $invoicingProcessRepository->findBy([
            'forecastAccount' => $forecastAccount,
        ], [
            'createdAt' => 'DESC',
        ]);

        return $this->render('organization/invoicing/index.html.twig', [
            'forecastAccount' => $forecastAccount,
            'invoicingProcesses' => $invoicingProcesses,
        ]);
    }

    /**
     * @Route("/create", name="create")
     */
    public function create(Request $request, ForecastAccount $forecastAccount, UserRepository $userRepository)
    {
        $invoicingProcess = new InvoicingProcess();
        $user = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $invoicingProcess->setCreatedBy($user);
        $invoicingProcess->setForecastAccount($forecastAccount);
        $invoicingProcess->setHarvestAccount($forecastAccount->getHarvestAccount());
        $invoicingProcess->setBillingPeriodStart(new \DateTime('first day of previous month'));
        $invoicingProcess->setBillingPeriodEnd(new \DateTime('last day of previous month'));
        $form = $this->createForm(InvoicingCreateType::class, $invoicingProcess);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $invoicingProcess = $form->getData();
            $this->invoicingStateMachine->getMarking($invoicingProcess);
            $this->em->persist($invoicingProcess);
            $this->em->flush();

            return $this->redirectToRoute('organization_invoicing_index', ['slug' => $forecastAccount->getSlug()]);
        }

        return $this->render('organization/invoicing/create.html.twig', [
            'forecastAccount' => $forecastAccount,
            'form' => $form->createView(),
            'invoicingProcess' => $invoicingProcess,
        ]);
    }

    /**
     * @Route("/{invoicingId}/clear-cache", name="clear_cache")
     * @ParamConverter("invoicingProcess", options={"id" = "invoicingId"})
     */
    public function clearCache(ForecastAccount $forecastAccount, InvoicingProcess $invoicingProcess)
    {
        $this->forecastClient->__clearCache();
        $this->harvestClient->__clearCache();

        return $this->resume($forecastAccount, $invoicingProcess);
    }

    /**
     * @Route("/{invoicingId}/explain/{explanationKey}", name="explain")
     * @ParamConverter("invoicingProcess", options={"id" = "invoicingId"})
     */
    public function explain(Request $request, ForecastAccount $forecastAccount, InvoicingProcess $invoicingProcess, string $explanationKey, UserRepository $userRepository, InvoiceExplanationRepository $invoiceExplanationRepository)
    {
        $invoiceExplanation = $invoiceExplanationRepository->findOneBy([
            'invoicingProcess' => $invoicingProcess,
            'explanationKey' => $explanationKey,
        ]);

        if (!$invoiceExplanation) {
            $user = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
            $invoiceExplanation = new InvoiceExplanation();
            $invoiceExplanation->setInvoicingProcess($invoicingProcess);
            $invoiceExplanation->setCreatedBy($user);
            $invoiceExplanation->setExplanationKey($explanationKey);
        }

        $form = $this->createForm(InvoiceExplanationType::class, $invoiceExplanation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $invoiceExplanation = $form->getData();
            $this->em->persist($invoiceExplanation);
            $this->em->flush();

            $this->addFlash(
                'confirm',
                'The explanation has been saved'
            );
        }

        return $this->render('organization/invoicing/explanation.html.twig', [
            'invoiceExplanation' => $invoiceExplanation,
            'forecastAccount' => $forecastAccount,
            'form' => $form->createView(),
            'invoicingProcess' => $invoicingProcess,
            'explanationKey' => $explanationKey,
        ]);
    }

    /**
     * @Route("/{invoicingId}/explain/{explanationKey}/delete", name="explaination_delete")
     * @ParamConverter("invoicingProcess", options={"id" = "invoicingId"})
     */
    public function deleteExplanation(Request $request, ForecastAccount $forecastAccount, InvoicingProcess $invoicingProcess, string $explanationKey, InvoiceExplanationRepository $invoiceExplanationRepository)
    {
        $invoiceExplanation = $invoiceExplanationRepository->findOneBy([
            'invoicingProcess' => $invoicingProcess,
            'explanationKey' => $explanationKey,
        ]);

        if (!$invoiceExplanation) {
            throw $this->createNotFoundException(sprintf('Could not find the explanation "%s" for the invoicing process "%s".', $explanationKey, $invoicingProcess->getId()));
        }

        $this->em->remove($invoiceExplanation);
        $this->em->flush();

        return new Response('');
    }

    /**
     * @Route("/{invoicingId}/resume", name="resume")
     * @ParamConverter("invoicingProcess", options={"id" = "invoicingId"})
     */
    public function resume(ForecastAccount $forecastAccount, InvoicingProcess $invoicingProcess)
    {
        return $this->redirectToRoute('organization_invoicing_transition', [
            'invoicingId' => $invoicingProcess->getId(),
            'slug' => $forecastAccount->getSlug(),
            'transition' => $this->getNextNaturalTransitionName($invoicingProcess),
        ]);
    }

    /**
     * @Route("/{invoicingId}/{transition}", name="transition", requirements={"transition": "collect|reconcile|approve|check|validate|completed"})
     * @ParamConverter("invoicingProcess", options={"id" = "invoicingId"})
     */
    public function transition(Request $request, ForecastAccount $forecastAccount, InvoicingProcess $invoicingProcess, string $transition)
    {
        $form = $this->createForm(InvoicingProgressType::class, null);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->progress($forecastAccount, $invoicingProcess, $transition);
        }

        $parameters = array_merge([
                'forecastAccount' => $forecastAccount,
                'form' => $form->createView(),
                'invoicingProcess' => $invoicingProcess,
                'transition' => $transition,
            ], $this->invoicingManager->$transition($invoicingProcess)
        );

        return $this->render('organization/invoicing/transition/' . $transition . '.html.twig', $parameters);
    }

    private function getNextNaturalTransitionName(InvoicingProcess $invoicingProcess)
    {
        $transitionNames = [
            'created' => 'collect',
            'timesheets_collected' => 'reconcile',
            'forecast_reconciliated' => 'approve',
            'timesheets_approved' => 'check',
            'all_hours_invoiced' => 'validate',
            'completed' => 'completed',
        ];

        return $transitionNames[$invoicingProcess->getCurrentPlace()];
    }

    private function progress(ForecastAccount $forecastAccount, InvoicingProcess $invoicingProcess, string $transition)
    {
        if ($this->invoicingStateMachine->can($invoicingProcess, $transition)) {
            $this->invoicingStateMachine->apply($invoicingProcess, $transition);
            $this->em->flush();
        }

        return $this->resume($forecastAccount, $invoicingProcess);
    }
}
