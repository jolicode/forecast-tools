<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\StandupMeetingReminder;

use App\DataSelector\ForecastDataSelector;
use App\DataSelector\SlackDataSelector;
use App\Entity\StandupMeetingReminder;
use App\Repository\ForecastAccountRepository;
use App\Repository\SlackTeamRepository;
use App\Repository\StandupMeetingReminderRepository;
use App\Slack\Sender as SlackSender;
use Doctrine\ORM\EntityManagerInterface;
use JoliCode\Slack\Exception\SlackErrorResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use function Symfony\Component\String\u;

class Handler
{
    final public const ACTION_PREFIX = 'standup-reminder';
    final public const ACTION_CHANGE = 'change';
    final public const ACTION_CREATE = 'create';

    final public const SLACK_COMMAND_NAME = '/standup-reminder';
    final public const SLACK_COMMAND_OPTION_HELP = 'help';
    final public const SLACK_COMMAND_OPTION_LIST = 'list';

    public function __construct(private readonly EntityManagerInterface $em, private readonly ForecastAccountRepository $forecastAccountRepository, private readonly ForecastDataSelector $forecastDataSelector, private readonly SlackDataSelector $slackDataSelector, private readonly SlackSender $slackSender, private readonly SlackTeamRepository $slackTeamRepository, private readonly StandupMeetingReminderRepository $standupMeetingReminderRepository)
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
            case self::SLACK_COMMAND_OPTION_LIST:
                $this->listReminders($request);

                // try to preload available clients and projects so that they're in cache
                $this->loadProjects($request->request->get('team_id'));
                break;
            case '':
                $this->openModal($request);
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
        $slackTeam = $this->slackTeamRepository->findOneBy([
            'teamId' => $payload['team']['id'],
        ]);

        $action = $payload['actions'][0];

        if (self::ACTION_PREFIX . '.' . self::ACTION_CHANGE === $action['action_id']) {
            $standupMeetingReminder = $this->standupMeetingReminderRepository->findOneBy([
                'id' => $action['block_id'],
                'slackTeam' => $slackTeam,
            ]);

            if ('delete' === $action['selected_option']['value']) {
                $channelId = $standupMeetingReminder->getChannelId();
                $this->em->remove($standupMeetingReminder);
                $this->em->flush();

                $client = \JoliCode\Slack\ClientFactory::create($slackTeam->getAccessToken());
                $message = sprintf(
                    '<@%s> removed a stand-up reminder from this channel.',
                    $payload['user']['username']
                );
                $client->chatPostMessage([
                    'channel' => $channelId,
                    'text' => $message,
                    'blocks' => json_encode([
                        [
                            'type' => 'section',
                            'text' => [
                                'type' => 'mrkdwn',
                                'text' => $message,
                            ],
                        ],
                    ]),
                ]);
            } elseif ('edit' === $action['selected_option']['value']) {
                $this->displayModalForm(
                    $payload['team']['id'],
                    $standupMeetingReminder->getChannelId(),
                    $payload['trigger_id'],
                );
            }
        } elseif (self::ACTION_PREFIX . '.' . self::ACTION_CREATE === $action['action_id']) {
            $this->displayModalForm(
                $payload['team']['id'],
                $payload['channel']['id'],
                $payload['trigger_id'],
                $action['value']
            );
        }

        $this->sendRemindersList(
            $payload['team']['id'],
            $payload['trigger_id'],
            $payload['response_url']
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handleSubmission(array $payload): JsonResponse
    {
        if (
            0 === (is_countable($payload['view']['state']['values']['clients']['selected_clients']['selected_options']) ? \count($payload['view']['state']['values']['clients']['selected_clients']['selected_options']) : 0)
            + (is_countable($payload['view']['state']['values']['projects']['selected_projects']['selected_options']) ? \count($payload['view']['state']['values']['projects']['selected_projects']['selected_options']) : 0)
        ) {
            return new JsonResponse([
                'response_action' => 'errors',
                'errors' => [
                    'clients' => 'Please choose at least one client or project.',
                    'projects' => 'Please choose at least one client or project.',
                ],
            ]);
        }

        $selectedClientsForDisplay = [];
        $selectedClientIds = [];
        $selectedProjectsForDisplay = [];
        $selectedProjectIds = [];
        $restrictionDescription = '';
        $slackTeam = $this->slackTeamRepository->findOneBy([
            'teamId' => $payload['team']['id'],
        ]);
        $privateMetadata = json_decode((string) $payload['view']['private_metadata'], true, 512, \JSON_THROW_ON_ERROR);

        if (isset($payload['view']['state']['values']['channel']['selected_channel']['selected_channel'])) {
            $channelId = $payload['view']['state']['values']['channel']['selected_channel']['selected_channel'];
        } else {
            $channelId = $privateMetadata['channel_id'];
        }

        foreach ($payload['view']['state']['values']['clients']['selected_clients']['selected_options'] as $client) {
            $selectedClientsForDisplay[] = sprintf('"%s"', $client['text']['text']);
            $selectedClientIds[] = $client['value'];
        }

        foreach ($payload['view']['state']['values']['projects']['selected_projects']['selected_options'] as $project) {
            $selectedProjectsForDisplay[] = sprintf('"%s"', $project['text']['text']);
            $selectedProjectIds[] = $project['value'];
        }

        $projectsByAccount = $this->loadProjects($payload['team']['id']);

        foreach ($projectsByAccount as $data) {
            foreach ($data['projects'] as $project) {
                if (
                    \count($selectedClientIds) > 0
                    && \in_array($project->getId(), $selectedProjectIds, true)
                    && !\in_array($project->getClientId(), $selectedClientIds, true)
                ) {
                    return new JsonResponse([
                        'response_action' => 'errors',
                        'errors' => [
                            'projects' => 'Please choose projects that match the selected client(s).',
                        ],
                    ]);
                }
            }
        }

        if (\count($selectedClientsForDisplay) > 0) {
            $restrictionDescription .= ' for the client(s) ' . u(', ')->join($selectedClientsForDisplay, ' and ');
        }

        if (\count($selectedProjectsForDisplay) > 0) {
            $restrictionDescription .= ' on the project(s) ' . u(', ')->join($selectedProjectsForDisplay, ' and ');
        }

        $selectedTime = $payload['view']['state']['values']['time']['selected_time']['selected_option']['value'];
        $standupMeetingReminder = $this->standupMeetingReminderRepository->findOneBy([
            'channelId' => $channelId,
            'slackTeam' => $slackTeam,
        ]);
        $actionName = 'updated';

        if (null === $standupMeetingReminder) {
            $standupMeetingReminder = new StandupMeetingReminder();
            $standupMeetingReminder->setChannelId($channelId);
            $standupMeetingReminder->setSlackTeam($slackTeam);
            $actionName = 'created';
        }

        $standupMeetingReminder->setUpdatedBy('@' . $payload['user']['username']);
        $standupMeetingReminder->setIsEnabled(true);
        $standupMeetingReminder->setForecastClients($selectedClientIds);
        $standupMeetingReminder->setForecastProjects($selectedProjectIds);
        $standupMeetingReminder->setTime($selectedTime);
        $this->em->persist($standupMeetingReminder);
        $this->em->flush();

        $client = \JoliCode\Slack\ClientFactory::create($slackTeam->getAccessToken());
        $message = sprintf(
            '<@%s> %s a stand-up reminder in this channel. It will run each day at `%s` and ping people working%s.',
            $payload['user']['username'],
            $actionName,
            $selectedTime,
            $restrictionDescription
        );
        $client->chatPostMessage([
            'channel' => $channelId,
            'text' => $message,
            'blocks' => json_encode([
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => $message,
                    ],
                ],
            ]),
        ]);

        if (isset($privateMetadata['response_url'])) {
            try {
                $this->sendRemindersList(
                    $slackTeam->getTeamId(),
                    $payload['trigger_id'],
                    $privateMetadata['response_url']
                );
            } catch (\Exception) {
                // silence, the window might just be opened since a long time
            }
        }

        return new JsonResponse(['response_action' => 'clear']);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, array<array-key, array<string, array<int|string, mixed>>>>
     */
    public function listClients(array $payload): array
    {
        $availableClients = [];
        $searched = mb_strtolower((string) $payload['value']);
        $projectsByAccount = $this->loadProjects($payload['team']['id']);

        foreach ($projectsByAccount as $data) {
            $accountClients = [];

            foreach ($data['clients'] as $client) {
                if (false !== mb_strpos(mb_strtolower((string) $client->getName()), $searched)) {
                    $accountClients[] = [
                        'text' => [
                            'type' => 'plain_text',
                            'text' => mb_substr((string) $client->getName(), 0, 75),
                        ],
                        'value' => (string) $client->getId(),
                    ];
                }
            }

            if (\count($accountClients) > 0) {
                usort($accountClients, fn ($a, $b) => strcmp($a['text']['text'], $b['text']['text']));

                $availableClients[] = [
                    'label' => [
                        'type' => 'plain_text',
                        'text' => $data['forecastAccount']->getName(),
                    ],
                    'options' => $accountClients,
                ];
            }
        }

        if (1 === \count($availableClients)) {
            return [
                'options' => $availableClients[0]['options'],
            ];
        }

        return [
            'option_groups' => $availableClients,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, array<array-key, array<string, array<int|string, mixed>>>>
     */
    public function listProjects(array $payload): array
    {
        $availableProjects = [];
        $searched = mb_strtolower((string) $payload['value']);
        $projectsByAccount = $this->loadProjects($payload['team']['id']);

        foreach ($projectsByAccount as $data) {
            $accountProjects = [];

            foreach ($data['projects'] as $project) {
                $clientName = isset($data['clients'][$project->getClientId()]) ? $data['clients'][$project->getClientId()]->getName() : '';

                if (false !== mb_strpos(mb_strtolower((string) $project->getName()), $searched) || false !== mb_strpos(mb_strtolower((string) $project->getCode()), $searched) || false !== mb_strpos(mb_strtolower((string) $clientName), $searched)) {
                    $projectCode = $project->getCode() ? '[' . $project->getCode() . '] ' : '';
                    $accountProjects[] = [
                        'text' => [
                            'type' => 'plain_text',
                            'text' => mb_substr(sprintf('%s%s%s', $projectCode, $clientName ? $clientName . ' - ' : '', $project->getName()), 0, 75),
                        ],
                        'value' => (string) $project->getId(),
                    ];
                }
            }

            if (\count($accountProjects) > 0) {
                usort($accountProjects, function ($a, $b) {
                    if (preg_match('/^\[[^0-9]*(\d+)\] .*$/', $a['text']['text'], $aMatches)) {
                        if (preg_match('/^\[[^0-9]*(\d+)\] .*$/', $b['text']['text'], $bMatches)) {
                            return ($aMatches[1] < $bMatches[1]) ? -1 : 1;
                        }

                        return 1;
                    }

                    return ($a['text']['text'] < $b['text']['text']) ? -1 : 1;
                });

                $availableProjects[] = [
                    'label' => [
                        'type' => 'plain_text',
                        'text' => $data['forecastAccount']->getName(),
                    ],
                    'options' => $accountProjects,
                ];
            }
        }

        if (1 === \count($availableProjects)) {
            return [
                'options' => $availableProjects[0]['options'],
            ];
        }

        return [
            'option_groups' => $availableProjects,
        ];
    }

    /**
     * @return array<array-key, array<string, mixed>>
     */
    public function loadProjects(string $teamId): array
    {
        $forecastAccounts = $this->forecastAccountRepository->findBySlackTeamId($teamId);
        $projectsByAccount = [];

        foreach ($forecastAccounts as $forecastAccount) {
            $this->forecastDataSelector->setForecastAccount($forecastAccount);
            $projectsByAccount[] = [
                'forecastAccount' => $forecastAccount,
                'clients' => $this->forecastDataSelector->getClientsById(true),
                'projects' => $this->forecastDataSelector->getProjects(true),
            ];
        }

        usort($projectsByAccount, fn ($a, $b) => $a['forecastAccount']->getName() < $b['forecastAccount']->getName() ? -1 : 1);

        return $projectsByAccount;
    }

    private function help(string $responseUrl, string $triggerId): void
    {
        $message = sprintf(<<<'EOT'
Use `%s` to create or edit a stand-up reminder.
Use `%s %s` to list all the existing stand-up reminders in the workspace.
EOT,
            self::SLACK_COMMAND_NAME,
            self::SLACK_COMMAND_NAME,
            self::SLACK_COMMAND_OPTION_LIST
        );
        $this->slackSender->sendMessage($responseUrl, $triggerId, $message);
    }

    private function listReminders(Request $request): void
    {
        $this->sendRemindersList(
            $request->request->get('team_id'),
            $request->request->get('trigger_id'),
            $request->request->get('response_url')
        );
    }

    private function openModal(Request $request): void
    {
        $slackTeam = $this->slackTeamRepository->findOneByTeamId($request->request->get('team_id'));
        $channelId = $request->request->get('channel_id');

        try {
            $this->slackDataSelector->getConversationInfos($slackTeam, $channelId);
        } catch (SlackErrorResponse) {
            // this is not a public channel, do not prefill it in the modal
            $channelId = null;
        }

        $this->displayModalForm(
            $request->request->get('team_id'),
            $channelId,
            $request->request->get('trigger_id')
        );
    }

    private function displayModalForm(string $teamId, ?string $channelId, string $triggerId, ?string $responseUrl = null): void
    {
        // get the forecast accounts which have a SlackTeam in this organization
        $forecastAccounts = $this->forecastAccountRepository->findBySlackTeamId($teamId);
        $slackTeam = $this->slackTeamRepository->findOneByTeamId($teamId);
        $availableTimes = [];
        $initialProjects = [];
        $initialClients = [];
        $initialTime = null;
        $initialHour = 10;
        $initialMinute = 0;
        $blocks = [];
        $privateMetadata = [
            'channel_id' => $channelId,
        ];

        if (null !== $channelId && null === $responseUrl) {
            // search if there's already a StandupMeetingReminder in this channel
            $standupMeetingReminder = $this->standupMeetingReminderRepository->findOneBy([
                'channelId' => $channelId,
                'slackTeam' => $slackTeam,
            ]);
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => sprintf('*Posts to* <#%s>', $channelId),
                ],
            ];
            $blocks[] = [
                'type' => 'divider',
            ];

            if (null !== $standupMeetingReminder) {
                [$initialHour, $initialMinute] = explode(':', $standupMeetingReminder->getTime());

                foreach ($forecastAccounts as $forecastAccount) {
                    $this->forecastDataSelector->setForecastAccount($forecastAccount);
                    $clients = $this->forecastDataSelector->getClients(true);
                    $projects = $this->forecastDataSelector->getProjects(true);

                    foreach ($clients as $client) {
                        if (\in_array((string) $client->getId(), $standupMeetingReminder->getForecastClients(), true)) {
                            $initialClients[] = [
                                'text' => [
                                    'type' => 'plain_text',
                                    'text' => mb_substr($client->getName(), 0, 75),
                                ],
                                'value' => (string) $client->getId(),
                            ];
                        }
                    }

                    foreach ($projects as $project) {
                        if (\in_array((string) $project->getId(), $standupMeetingReminder->getForecastProjects(), true)) {
                            $projectCode = null !== $project->getCode() && '' !== $project->getCode() ? '[' . $project->getCode() . '] ' : '';
                            $initialProjects[] = [
                                'text' => [
                                    'type' => 'plain_text',
                                    'text' => mb_substr($projectCode . $project->getName(), 0, 75),
                                ],
                                'value' => (string) $project->getId(),
                            ];
                        }
                    }
                }
            }
        } else {
            $blocks[] = [
                'type' => 'input',
                'block_id' => 'channel',
                'label' => [
                    'type' => 'plain_text',
                    'text' => 'Channel',
                ],
                'element' => [
                    'type' => 'channels_select',
                    'action_id' => 'selected_channel',
                    'placeholder' => [
                        'type' => 'plain_text',
                        'text' => 'Select a public channel',
                    ],
                ],
            ];

            if (null !== $responseUrl) {
                $privateMetadata['response_url'] = $responseUrl;
            }
        }

        foreach (range(0, 23) as $hour) {
            foreach (range(0, 45, 15) as $minute) {
                $timeItem = [
                    'text' => [
                        'type' => 'plain_text',
                        'text' => sprintf('%02d:%02d', $hour, $minute),
                    ],
                    'value' => sprintf('%02d:%02d', $hour, $minute),
                ];
                $availableTimes[] = $timeItem;

                if ($hour === (int) $initialHour && $minute === (int) $initialMinute) {
                    $initialTime = $timeItem;
                }
            }
        }

        $clientsBlock = [
            'type' => 'input',
            'block_id' => 'clients',
            'label' => [
                'type' => 'plain_text',
                'text' => 'Choose clients',
            ],
            'element' => [
                'type' => 'multi_external_select',
                'action_id' => 'selected_clients',
                'placeholder' => [
                    'type' => 'plain_text',
                    'text' => 'Select clients',
                ],
                'min_query_length' => 3,
            ],
            'optional' => true,
        ];

        if (\count($initialClients) > 0) {
            $clientsBlock['element']['initial_options'] = $initialClients;
        }

        $projectsBlock = [
            'type' => 'input',
            'block_id' => 'projects',
            'label' => [
                'type' => 'plain_text',
                'text' => 'Choose Forecast projects',
            ],
            'element' => [
                'type' => 'multi_external_select',
                'action_id' => 'selected_projects',
                'placeholder' => [
                    'type' => 'plain_text',
                    'text' => 'Select projects',
                ],
                'min_query_length' => 3,
            ],
            'optional' => true,
            'hint' => [
                'type' => 'plain_text',
                'text' => 'Choose one or more projects to restrict the notification to these projects only. If no specific project is choosen, all the projects attached to the selected client(s) will be used.',
            ],
        ];

        if (\count($initialProjects) > 0) {
            $projectsBlock['element']['initial_options'] = $initialProjects;
        }

        $blocks[] = $clientsBlock;
        $blocks[] = $projectsBlock;
        $blocks[] = [
            'type' => 'input',
            'block_id' => 'time',
            'label' => [
                'type' => 'plain_text',
                'text' => 'At what time?',
            ],
            'element' => [
                'type' => 'static_select',
                'action_id' => 'selected_time',
                'placeholder' => [
                    'type' => 'plain_text',
                    'text' => 'Select a time in the day',
                ],
                'options' => $availableTimes,
                'initial_option' => $initialTime,
            ],
        ];
        $body = [
            'trigger_id' => $triggerId,
            'view' => [
                'type' => 'modal',
                'private_metadata' => json_encode($privateMetadata, \JSON_THROW_ON_ERROR),
                'title' => [
                    'type' => 'plain_text',
                    'text' => 'Stand-up Reminder',
                ],
                'submit' => [
                    'type' => 'plain_text',
                    'text' => 'Save',
                ],
                'close' => [
                    'type' => 'plain_text',
                    'text' => 'Cancel',
                ],
                'blocks' => $blocks,
            ],
        ];
        $this->slackSender->send('https://slack.com/api/views.open', $body, [
            'Authorization' => 'Bearer ' . $slackTeam->getAccessToken(),
        ]);
    }

    private function sendRemindersList(string $teamId, string $triggerId, string $responseUrl): void
    {
        $slackTeam = $this->slackTeamRepository->findOneBy([
            'teamId' => $teamId,
        ]);
        $reminderBlocks = [];

        foreach ($slackTeam->getStandupMeetingReminders() as $standupMeetingReminder) {
            $reminderBlocks[] = [
                'block_id' => (string) $standupMeetingReminder->getId(),
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => sprintf(
                        '<#%s> each day at `%s`',
                        $standupMeetingReminder->getChannelId(),
                        $standupMeetingReminder->getTime()
                    ),
                ],
                'accessory' => [
                    'type' => 'overflow',
                    'options' => [
                        [
                            'text' => [
                                'type' => 'plain_text',
                                'text' => 'Edit',
                            ],
                            'value' => 'edit',
                        ], [
                            'text' => [
                                'type' => 'plain_text',
                                'text' => 'Delete',
                            ],
                            'value' => 'delete',
                        ],
                    ],
                    'action_id' => self::ACTION_PREFIX . '.' . self::ACTION_CHANGE,
                ],
            ];
        }

        if (0 === \count($reminderBlocks)) {
            $reminderBlocks = [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => 'No stand-up reminder has been configured in your Slack team.',
                    ],
                ],
            ];
        }

        $reminderBlocks[] = [
            'block_id' => 'main_actions',
            'type' => 'actions',
            'elements' => [
                [
                    'type' => 'button',
                    'style' => 'primary',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => 'Schedule a stand-up reminder',
                    ],
                    'value' => $responseUrl,
                    'action_id' => self::ACTION_PREFIX . '.' . self::ACTION_CREATE,
                ],
            ],
        ];
        $body = [
            'trigger_id' => $triggerId,
            'blocks' => $reminderBlocks,
        ];
        $this->slackSender->send($responseUrl, $body);
    }
}
