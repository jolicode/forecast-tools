<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\ForecastReminder;

use App\Converter\WordToNumberConverter;
use App\Entity\ForecastReminder;
use App\Repository\ForecastReminderRepository;
use App\Slack\Sender as SlackSender;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;

class Handler
{
    const SLACK_COMMAND_NAME = '/forecast';
    const SLACK_COMMAND_OPTION_HELP = 'help';

    private ForecastReminderRepository $forecastReminderRepository;
    private SlackSender $slackSender;
    private WordToNumberConverter $wordToNumberConverter;

    public function __construct(ForecastReminderRepository $forecastReminderRepository, SlackSender $slackSender, WordToNumberConverter $wordToNumberConverter)
    {
        $this->forecastReminderRepository = $forecastReminderRepository;
        $this->slackSender = $slackSender;
        $this->wordToNumberConverter = $wordToNumberConverter;
    }

    public function handleRequest(Request $request)
    {
        $option = $request->request->get('text', '');

        if (self::SLACK_COMMAND_OPTION_HELP === $option) {
            $this->help(
                $request->request->get('response_url'),
                $request->request->get('trigger_id')
            );
        } else {
            $this->slackSender->send(
                $request->request->get('response_url'),
                $request->request->get('trigger_id'),
                '⌛ Computing the requested forecast...'
            );
            $this->sendForecastReminders($request);
        }
    }

    private function buildReminder(ForecastReminder $forecastReminder, ?string $text = ''): array
    {
        $startDate = $this->extractStartDateFromtext($text);
        $builder = new Builder($forecastReminder);
        $title = $builder->buildTitle($startDate);
        $message = $builder->buildMessage($startDate);

        return [
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => $title,
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => $message,
                    ],
                ],
            ],
            'replace_original' => true,
        ];
    }

    private function extractStartDateFromtext(string $text): \DateTime
    {
        if ('' === $text) {
            $text = 'tomorrow';
        }

        $text = $this->wordToNumberConverter->convert($text);
        $sign = '';

        if (0 === strncmp($text, 'in ', 3)) {
            $text = '+' . substr($text, 3);
        }

        if (0 === substr_compare($text, ' ago', -4)) {
            $text = '-' . substr($text, 0, \strlen($text) - 4);
            $sign = '-';
        }

        $text = str_replace(' and ', ', ' . $sign, $text);
        $datetime = new \DateTime($text);

        if ($datetime && 0 === \DateTime::getLastErrors()['warning_count'] && 0 === \DateTime::getLastErrors()['error_count']) {
            return $datetime;
        }

        return new \DateTime('+1 day');
    }

    private function help(string $responseUrl, string $triggerId)
    {
        $message = sprintf(<<<'EOT'
The `%s` command displays the team's Forecast schedule for a given day. Use _time parameters_ to choose a specific date:

➡️ `%s` _displays tomorrow's schedule_
➡️ `%s tomorrow`
➡️ `%s yesterday`
➡️ `%s today`
➡️ `%s next wednesday`
➡️ `%s in one week and three days`
➡️ `%s 2 weeks ago`
EOT,
            self::SLACK_COMMAND_NAME,
            self::SLACK_COMMAND_NAME,
            self::SLACK_COMMAND_NAME,
            self::SLACK_COMMAND_NAME,
            self::SLACK_COMMAND_NAME,
            self::SLACK_COMMAND_NAME,
            self::SLACK_COMMAND_NAME,
            self::SLACK_COMMAND_NAME
        );
        $this->slackSender->send($responseUrl, $triggerId, $message);
    }

    private function sendForecastReminders(Request $request)
    {
        $forecastReminders = $this->forecastReminderRepository->findByTeamId($request->request->get('team_id'));

        if (0 === \count($forecastReminders)) {
            $this->slackSender->send(
                $request->request->get('response_url'),
                $request->request->get('trigger_id'),
                'Your Slack team is not configured in Forecast tools, or the Slack reminder has not been enabled. Oh dear, why do you have our app installed? 🙃'
            );
        } else {
            foreach ($forecastReminders as $forecastReminder) {
                $body = $this->buildReminder($forecastReminder, $request->request->get('text'));
                $this->sendMessage(
                    $request->request->get('response_url'),
                    $body
                );
            }
        }
    }

    private function sendMessage(string $responseUrl, array $body)
    {
        $client = HttpClient::create();
        $client->request('POST', $responseUrl, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($body),
        ]);
    }
}
