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

use App\Client\HarvestClient;
use App\Entity\ForecastAccount;
use App\Form\CleanupType;
use JoliCode\Harvest\Api\Model\ClientsClientIdPatchBody;
use JoliCode\Harvest\Api\Model\ProjectsProjectIdPatchBody;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/{slug}/cleanup', name: 'organization_cleanup_', defaults: ['menu' => 'cleanup'])]
#[IsGranted('admin', subject: 'forecastAccount')]
#[IsGranted('harvest_admin', subject: 'forecastAccount')]
class CleanupController extends AbstractController
{
    public function __construct(
        private readonly HarvestClient $harvestClient,
    ) {
    }

    #[Route(path: '/', name: 'index')]
    public function index(Request $request, ForecastAccount $forecastAccount): \Symfony\Component\HttpFoundation\Response
    {
        $form = $this->createForm(CleanupType::class, null, ['harvestAccount' => $forecastAccount->getHarvestAccount()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            foreach ($data['project'] as $projectId) {
                // disabling a project on Harvest will also disable it on Forecast
                $patch = new ProjectsProjectIdPatchBody();
                $patch->setIsActive(false);
                $this->harvestClient->__client()->updateProject($projectId, $patch);
            }

            foreach ($data['client'] as $clientId) {
                // disabling a project on Harvest will also disable it on Forecast
                $patch = new ClientsClientIdPatchBody();
                $patch->setIsActive(false);
                $this->harvestClient->__client()->updateClient($clientId, $patch);
            }

            $this->addFlash(
                'success',
                '👋 The items have been archived!'
            );

            return $this->redirectToRoute('organization_cleanup_index', ['slug' => $forecastAccount->getSlug()]);
        }

        return $this->render('organization/cleanup/index.html.twig', [
            'forecastAccount' => $forecastAccount,
            'form' => $form,
            'controller_name' => 'CleanupController',
        ]);
    }
}
