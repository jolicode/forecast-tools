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
use Symfony\Component\HttpFoundation\Request;

class Handler
{
    final public const SLACK_COMMAND_NAME = '/forecast';
    final public const SLACK_COMMAND_OPTION_HELP = 'help';

    public function __construct(
        private readonly Builder $builder,
        private readonly ForecastReminderRepository $forecastReminderRepository,
        private readonly SlackSender $slackSender,
        private readonly WordToNumberConverter $wordToNumberConverter,
    ) {
    }

    public function handleRequest(Request $request): void
    {
        $option = $request->request->get('text', '');

        if (self::SLACK_COMMAND_OPTION_HELP === $option) {
            $this->help(
                $request->request->get('response_url'),
                $request->request->get('trigger_id')
            );
        } else {
            $this->slackSender->sendMessage(
                $request->request->get('response_url'),
                $request->request->get('trigger_id'),
                '⌛ Computing the requested forecast...'
            );
            $this->sendForecastReminders($request);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReminder(ForecastReminder $forecastReminder, ?string $text = ''): array
    {
        $startDate = $this->extractStartDateFromtext($text);
        $blocks = $this->builder->buildBlocks($forecastReminder, $startDate);
        unset($blocks['successful']);
        $blocks['replace_original'] = true;

        return $blocks;
    }

    private function extractStartDateFromtext(string $text): \DateTime
    {
        if ('' === $text) {
            $text = 'tomorrow';
        }

        $text = $this->wordToNumberConverter->convert($text);
        $sign = '';

        if (str_starts_with($text, 'in ')) {
            $text = '+' . substr($text, 3);
        }

        if (str_ends_with($text, ' ago')) {
            $text = '-' . substr($text, 0, \strlen($text) - 4);
            $sign = '-';
        }

        $text = str_replace(' and ', ', ' . $sign, $text);

        try {
            $datetime = new \DateTime($text);

            if (false === \DateTime::getLastErrors()) {
                return $datetime;
            }
        } catch (\Exception) {
            // silent the wrongly formatted date expression errors
        }

        return new \DateTime('+1 day');
    }

    private function help(string $responseUrl, string $triggerId): void
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
        $this->slackSender->sendMessage($responseUrl, $triggerId, $message);
    }

    private function sendForecastReminders(Request $request): void
    {
        $forecastReminders = $this->forecastReminderRepository->findByTeamId($request->request->get('team_id'));

        if (0 === \count($forecastReminders)) {
            $this->slackSender->sendMessage(
                $request->request->get('response_url'),
                $request->request->get('trigger_id'),
                'Your Slack team is not configured in Forecast tools, or the Slack reminder has not been enabled. Oh dear, why do you have our app installed? 🙃'
            );
        } else {
            foreach ($forecastReminders as $forecastReminder) {
                $body = $this->buildReminder($forecastReminder, $request->request->get('text'));
                $this->slackSender->send(
                    $request->request->get('response_url'),
                    $body
                );
            }
        }
    }
}
