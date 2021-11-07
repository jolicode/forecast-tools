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

use App\Harvest\Handler as HarvestReminderHandler;
use App\StandupMeetingReminder\Handler as StandupMeetingReminderHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/slack", name="slack_")
 */
class SlackCommandController extends AbstractController
{
    /**
     * @Route("/command", name="command")
     */
    public function command(Request $request)
    {
        return new Response('');
    }

    /**
     * @Route("/data-source", name="data_source")
     */
    public function dataSource(Request $request, StandupMeetingReminderHandler $standupMeetingReminderHandler): JsonResponse
    {
        $payload = json_decode($request->request->get('payload'), true);

        if ('selected_projects' === $payload['action_id']) {
            return new JsonResponse($standupMeetingReminderHandler->listProjects($payload));
        }

        return new JsonResponse('<3 you, Slack');
    }

    /**
     * @Route("/interactive-endpoint", name="interactive_endpoint")
     */
    public function interactiveEndpoint(Request $request, StandupMeetingReminderHandler $standupMeetingReminderHandler, HarvestReminderHandler $harvestReminderHandler): JsonResponse
    {
        $payload = json_decode($request->request->get('payload'), true);

        if ('block_actions' === $payload['type']) {
            // whenever a block kit interactive component is clicked
            $action = explode('.', $payload['actions'][0]['action_id']);

            switch ($action[0]) {
                case StandupMeetingReminderHandler::ACTION_PREFIX:
                    $standupMeetingReminderHandler->handleBlockAction($payload);
                    break;
                case HarvestReminderHandler::ACTION_PREFIX:
                    $harvestReminderHandler->handleBlockAction($payload);
                    break;
                default:
                    throw new \DomainException(sprintf('Could not understand the "%s" action type.', $action[0]));
            }
        }

        if ('view_submission' === $payload['type']) {
            // whenever a modal is submitted
            return $standupMeetingReminderHandler->handleSubmission($payload);
        }

        return new JsonResponse('<3 you, Slack');
    }
}
