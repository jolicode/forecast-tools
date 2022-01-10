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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/{slug}/mass-insertion", name="organization_mass_insertion_", defaults={"menu": "mass-insertion"})
 * @IsGranted("admin", subject="forecastAccount")
 * @IsGranted("harvest_admin", subject="forecastAccount")
 */
class MassInsertionController extends AbstractController
{
    private ForecastClient $forecastClient;

    private ForecastDataSelector $forecastDataSelector;

    private HarvestClient $harvestClient;

    private HarvestDataSelector $harvestDataSelector;

    public function __construct(ForecastClient $forecastClient, HarvestClient $harvestClient, ForecastDataSelector $forecastDataSelector, HarvestDataSelector $harvestDataSelector)
    {
        $this->forecastClient = $forecastClient;
        $this->harvestClient = $harvestClient;
        $this->forecastDataSelector = $forecastDataSelector;
        $this->harvestDataSelector = $harvestDataSelector;
    }

    /**
     * @Route("/", name="index")
     */
    public function index(Request $request, ForecastAccount $forecastAccount)
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
                    /** @phpstan-ignore-next-line */
                    $harvestUserId = $forecastPeople[$personId]->getHarvestUserId();

                    // create the harvest timeEntry
                    $harvestBody = new TimeEntriesPostBody();
                    $harvestBody->setHours($data['duration']);
                    $harvestBody->setNotes($data['comment']);
                    /* @phpstan-ignore-next-line */
                    $harvestBody->setProjectId($harvestProjectId);
                    $harvestBody->setSpentDate($data['date']);
                    /* @phpstan-ignore-next-line */
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
            'form' => $form->createView(),
            'controller_name' => 'MassInsertionController',
        ]);
    }
}
