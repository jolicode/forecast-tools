<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Notification;

use Http\Client\HttpClient;
use Http\Message\MessageFactory;

class SlackNotifier
{
    private $httpClient;
    private $messageFactory;

    public function __construct(HttpClient $httpClient, MessageFactory $messageFactory)
    {
        $this->httpClient = $httpClient;
        $this->messageFactory = $messageFactory;
    }

    public function notify(string $title, string $message, string $webHook)
    {
        $payload = [
            'username' => 'Forecast bot',
            'icon_url' => 'https://forecastapp.com/assets/images/apple-touch-icon.png',
            'attachments' => [[
                'fallback' => $title,
                'pretext' => $title,
                'color' => 'good',
                'mrkdwn_in' => ['pretext', 'text', 'fields'],
                'fields' => [[
                    'value' => $message,
                    'short' => false,
                ]],
            ]],
        ];
        $response = $this->httpClient->sendRequest(
            $this->messageFactory->createRequest('POST', sprintf('https://hooks.slack.com/services/%s', $webHook), [
                'Content-type' => 'application/json',
            ], json_encode($payload))
        );
    }
}
