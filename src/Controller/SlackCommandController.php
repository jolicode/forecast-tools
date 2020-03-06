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

use App\StandupMeetingReminder\Handler;
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
    public function dataSource(Request $request, Handler $standupMeetingReminderHandler): JsonResponse
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
    public function interactiveEndpoint(Request $request, Handler $standupMeetingReminderHandler): JsonResponse
    {
        $payload = json_decode($request->request->get('payload'), true);

        if ('block_actions' === $payload['type']) {
            $standupMeetingReminderHandler->handleBlockAction($payload);
        }

        if ('view_submission' === $payload['type']) {
            return $standupMeetingReminderHandler->handleSubmission($payload);
        }

        return new JsonResponse('<3 you, Slack');
    }
}
