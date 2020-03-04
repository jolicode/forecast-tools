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

use App\Repository\ForecastReminderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
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
        if ('help' !== $request->request->get('text')) {
            $this->temporaryResponse($request->request->get('response_url'));
        }

        return new Response('');
    }

    /**
     * @Route("/interactive-endpoint", name="interactive_endpoint")
     */
    public function interactiveEndpoint(Request $request, ForecastReminderRepository $forecastReminderRepository): JsonResponse
    {
        $payload = json_decode($request->request->get('payload'), true);
        $this->temporaryResponse($payload['response_url']);

        return new JsonResponse('<3 you, Slack');
    }

    private function temporaryResponse($responseUrl)
    {
        $body = [
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => '_Computing the forecast, one moment please..._',
                    ],
                ],
            ],
        ];
        $client = HttpClient::create();
        $client->request('POST', $responseUrl, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($body),
        ]);
    }
}
