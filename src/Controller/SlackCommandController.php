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
        $this->temporaryResponse($request->request->get('response_url'));

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

    private function multipleReminders(array $forecastReminders): JsonResponse
    {
        $body = [
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => sprintf('It seems that you have configured %s reminders in this Slack workspace. Which one would you like to see?', \count($forecastReminders)),
                    ],
                ], [
                    'type' => 'divider',
                ],
            ],
        ];

        foreach ($forecastReminders as $forecastReminder) {
            $body['blocks'][] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => sprintf(
                        '%s - %s',
                        $forecastReminder->getForecastAccount()->getName(),
                        $forecastReminder->getName()
                    ),
                ],
                'accessory' => [
                    'type' => 'button',
                    'text' => [
                        'type' => 'plain_text',
                        'emoji' => true,
                        'text' => 'Choose',
                    ],
                    'value' => (string) $forecastReminder->getId(),
                ],
            ];
        }

        return new JsonResponse($body);
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
