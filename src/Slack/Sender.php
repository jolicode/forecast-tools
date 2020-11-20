<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Slack;

use App\Entity\SlackCall;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\ResponseInterface;

class Sender
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function sendMessage(string $responseUrl, string $triggerId, string $message, $ephemeral = false): ResponseInterface
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

        return $this->send($responseUrl, $body);
    }

    public function send(string $url, array $body, ?array $headers = []): ResponseInterface
    {
        $headers = array_merge($headers, [
            'Content-Type' => 'application/json; charset=utf-8',
        ]);
        $body = json_encode($body);

        $slackCall = new SlackCall();
        $slackCall->setUrl($url);
        $slackCall->setRequestBody($body);
        $this->em->persist($slackCall);
        $this->em->flush();

        $client = HttpClient::create();
        $response = $client->request('POST', $url, [
            'headers' => $headers,
            'body' => $body,
        ]);

        $slackCall->setResponseBody($response->getContent());
        $slackCall->setStatusCode($response->getStatusCode());
        $this->em->flush();

        return $response;
    }
}
