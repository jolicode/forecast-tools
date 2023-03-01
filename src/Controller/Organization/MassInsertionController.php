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
use App\DataSelector\ForecastDataSelector;
use App\DataSelector\HarvestDataSelector;
use App\Entity\ForecastAccount;
use App\Form\MassInsertType;
use JoliCode\Forecast\Api\Model\Assignment;
use JoliCode\Forecast\Api\Model\AssignmentsPostBody;
use JoliCode\Harvest\Api\Model\TimeEntriesPostBody;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/{slug}/mass-insertion', name: 'organization_mass_insertion_', defaults: ['menu' => 'mass-insertion'])]
#[IsGranted('admin', subject: 'forecastAccount')]
#[IsGranted('harvest_admin', subject: 'forecastAccount')]
class MassInsertionController extends AbstractController
{
    public function __construct(private readonly ForecastClient $forecastClient, private readonly HarvestClient $harvestClient, private readonly ForecastDataSelector $forecastDataSelector, private readonly HarvestDataSelector $harvestDataSelector)
    {
    }

    #[Route(path: '/', name: 'index')]
    public function index(Request $request, ForecastAccount $forecastAccount): \Symfony\Component\HttpFoundation\Response
    {
        $form = $this->createForm(MassInsertType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if (true === $data['harvest']) {
                // search the Harvest project
                $forecastProjects = $this->forecastDataSelector->getProjectsById(null, true);
                $harvestProjectId = $forecastProjects[$data['project']]->getHarvestId();
                $forecastPeople = $this->forecastDataSelector->getPeopleById();

                // search the task
                $harvestTasks = $this->harvestDataSelector->getTaskAssignmentsForProjectId($harvestProjectId);
                $harvestTaskAssignment = array_shift($harvestTasks);
            }

            foreach ($data['people'] as $personId) {
                if (true === $data['forecast']) {
                    $assignment = new Assignment();
                    $assignment->setAllocation($data['duration'] * 3600);
                    $assignment->setEndDate($data['date']);
                    $assignment->setNotes($data['comment']);
                    $assignment->setPersonId($personId);
                    $assignment->setProjectId($data['project']);
                    $assignment->setRepeatedAssignmentSetId(null);
                    $assignment->setStartDate($data['date']);

                    $forecastBody = new AssignmentsPostBody();
                    $forecastBody->setAssignment($assignment);
                    $this->forecastClient->__client()->createAssignment($forecastBody);
                }

                if (true === $data['harvest']) {
                    // search the harvest user
                    $harvestUserId = $forecastPeople[$personId]->getHarvestUserId();

                    // create the harvest timeEntry
                    $harvestBody = new TimeEntriesPostBody();
                    $harvestBody->setHours($data['duration']);
                    $harvestBody->setNotes($data['comment']);
                    $harvestBody->setProjectId($harvestProjectId);
                    $harvestBody->setSpentDate($data['date']);
                    $harvestBody->setTaskId($harvestTaskAssignment->getTask()->getId());
                    $harvestBody->setUserId($harvestUserId);
                    $this->harvestClient->__client()->createTimeEntry($harvestBody);
                }
            }

            $this->addFlash(
                'success',
                '👋 The entries have been added!'
            );

            return $this->redirectToRoute('organization_mass_insertion_index', ['slug' => $forecastAccount->getSlug()]);
        }

        return $this->render('organization/mass_insertion/index.html.twig', [
            'forecastAccount' => $forecastAccount,
            'form' => $form,
            'controller_name' => 'MassInsertionController',
        ]);
    }
}
