<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Harvest;

use App\DataSelector\HarvestDataSelector;
use App\DataSelector\SlackDataSelector;
use App\Entity\HarvestAccount;
use App\Repository\HarvestAccountRepository;
use App\Repository\SlackTeamRepository;
use App\Slack\Sender as SlackSender;
use JoliCode\Harvest\Api\Model\User as HarvestUser;
use Symfony\Component\HttpFoundation\Request;

class Handler
{
    final public const ACTION_PREFIX = 'timesheet-reminder';
    final public const ACTION_COPY = 'copy';
    final public const ACTION_RELOAD = 'reload';

    final public const SLACK_COMMAND_NAME = '/check-timesheets';
    final public const SLACK_COMMAND_OPTION_HELP = 'help';
    final public const SLACK_COMMAND_OPTION_CURRENT = 'current';

    public function __construct(private readonly HarvestAccountRepository $harvestAccountRepository, private readonly HarvestDataSelector $harvestDataSelector, private readonly Reminder $harvestTimesheetReminder, private readonly SlackDataSelector $slackDataSelector, private readonly SlackSender $slackSender, private readonly SlackTeamRepository $slackTeamRepository)
    {
    }

    public function handleRequest(Request $request): void
    {
        $option = $request->request->get('text', '');

        switch ($option) {
            case self::SLACK_COMMAND_OPTION_HELP:
                $this->help(
                    $request->request->get('response_url'),
                    $request->request->get('trigger_id')
                );
                break;
            case self::SLACK_COMMAND_OPTION_CURRENT:
            case '':
                $this->slackSender->sendMessage(
                    $request->request->get('response_url'),
                    $request->request->get('trigger_id'),
                    '⌛ Wait a second, please, we are checking your timesheets',
                    true
                );
                $harvestProperties = $this->retrieveHarvestPropertiesFromSlackPayload(
                    $request->request->get('team_id'),
                    $request->request->get('user_id')
                );

                if (null !== $harvestProperties) {
                    $this->updateReminder(
                        $harvestProperties['account'],
                        $harvestProperties['user'],
                        $request->request->get('trigger_id'),
                        $request->request->get('response_url'),
                        self::SLACK_COMMAND_OPTION_CURRENT === $option
                    );
                } else {
                    $message = 'Sorry, I could not identify you as a Harvest user from one of the Harvest accounts related to this Slack team. Please make sure that you use the same email address in Harvest and in Slack.';
                    $this->slackSender->sendMessage(
                        $request->request->get('response_url'),
                        $request->request->get('trigger_id'),
                        $message
                    );
                }

                break;
            default:
                throw new \DomainException(sprintf('😱 The "%s" option is not valid.', $option));
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handleBlockAction(array $payload): void
    {
        $action = $payload['actions'][0];

        switch ($action['action_id']) {
            case self::ACTION_PREFIX . '.' . self::ACTION_COPY:
                $this->slackSender->sendMessage($payload['response_url'], $payload['trigger_id'], '⌛ Okay, we are copying Forecast data to Harvest');
                $this->handleCopy($payload, $action['value']);
                break;
            case self::ACTION_PREFIX . '.' . self::ACTION_RELOAD:
                $this->slackSender->sendMessage($payload['response_url'], $payload['trigger_id'], '⌛ Okay, we are checking your timesheet again');
                $harvestProperties = $this->retrieveHarvestPropertiesFromSlackPayload($payload['user']['team_id'], $payload['user']['id']);

                if (null !== $harvestProperties) {
                    try {
                        $this->updateReminder(
                            $harvestProperties['account'],
                            $harvestProperties['user'],
                            $payload['trigger_id'],
                            $payload['response_url'],
                            self::SLACK_COMMAND_OPTION_CURRENT === $action['value']
                        );
                    } catch (\Exception) {
                        // silence, the initial reminder might be sent since a long time
                    }
                }
                break;
            default:
                throw new \DomainException(sprintf('Could not understand the "%s" action type.', $action['action_id']));
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function handleCopy(array $payload, string $value): void
    {
        $harvestProperties = $this->retrieveHarvestPropertiesFromSlackPayload($payload['user']['team_id'], $payload['user']['id']);

        if (null !== $harvestProperties) {
            $this->harvestTimesheetReminder->copy(
                $harvestProperties['account'],
                $harvestProperties['user'],
                $value
            );
            try {
                $current = (new \DateTime($value))->format('n') === (new \DateTime())->format('n');
                $this->updateReminder(
                    $harvestProperties['account'],
                    $harvestProperties['user'],
                    $payload['trigger_id'],
                    $payload['response_url'],
                    $current
                );
            } catch (\Exception) {
                // silence, the initial reminder might be sent since a long time
            }
        }
    }

    private function help(string $responseUrl, string $triggerId): void
    {
        $message = sprintf(<<<'EOT'
The `%s` command helps check and fill your Harvest timesheets, based on the Forecast schedule:

➡️ `%s` checks the last month's timesheets
➡️ `%s %s` checks your timesheets for the current month
EOT,
            self::SLACK_COMMAND_NAME,
            self::SLACK_COMMAND_NAME,
            self::SLACK_COMMAND_NAME,
            self::SLACK_COMMAND_OPTION_CURRENT
        );
        $this->slackSender->sendMessage($responseUrl, $triggerId, $message);
    }

    /**
     * @return array<string, HarvestAccount|HarvestUser>|null
     */
    private function retrieveHarvestPropertiesFromSlackPayload(string $teamId, string $userId): ?array
    {
        $slackTeam = $this->slackTeamRepository->findOneBy([
            'teamId' => $teamId,
        ]);
        $slackEmail = $this->slackDataSelector->getUserProfile($slackTeam, $userId)->getEmail();

        if (null !== $slackEmail) {
            // get a collection of possible harvest accounts
            $harvestAccounts = $this->harvestAccountRepository->findBySlackTeamId($teamId);

            foreach ($harvestAccounts as $harvestAccount) {
                $harvestUser = $this->harvestDataSelector
                    ->setHarvestAccount($harvestAccount)
                    ->getUserByEmail($slackEmail);

                if (null !== $harvestUser) {
                    return [
                        'account' => $harvestAccount,
                        'user' => $harvestUser,
                    ];
                }
            }
        }

        return null;
    }

    private function updateReminder(HarvestAccount $harvestAccount, HarvestUser $harvestUser, string $triggerId, string $responseUrl, ?bool $currentMonth = false): void
    {
        $issues = $this->harvestTimesheetReminder->buildForHarvestAccountAndUser($harvestAccount, $harvestUser, $currentMonth);

        if (isset($issues[$harvestUser->getId()])) {
            $this->slackSender->send($responseUrl, [
                'replace_original' => 'true',
                'trigger_id' => $triggerId,
                'text' => $issues[$harvestUser->getId()]['message'],
                'blocks' => $issues[$harvestUser->getId()]['blocks'],
            ]);
        } else {
            if ($currentMonth) {
                $targetDate = new \DateTime('first day of this month');
            } else {
                $targetDate = new \DateTime('first day of last month');
            }

            $message = sprintf(
                '🏆 %s, your timesheets are all good, thank you! Don\'t forget to <%s/time/week/%s/%s/01|validate your timesheets>.',
                $harvestUser->getFirstName(),
                $harvestAccount->getBaseUri(),
                $targetDate->format('Y'),
                $targetDate->format('m')
            );
            $this->slackSender->sendMessage($responseUrl, $triggerId, $message);
        }
    }
}
