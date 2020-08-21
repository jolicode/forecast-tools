<?php

namespace App\Slack;

use Symfony\Component\HttpClient\HttpClient;

class Sender
{
    public function send(string $responseUrl, string $triggerId, string $message, $ephemeral = false)
    {
        $body = [
            'replace_original' => 'true',
            'trigger_id' => $triggerId,
            'text' => $message,
            'blocks' => [[
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => $message,
                ],
            ]],
        ];

        if ($ephemeral) {
            $body['response_type'] = 'ephemeral';
        }

        $client = HttpClient::create();
        $client->request('POST', $responseUrl, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($body),
        ]);
    }
}
