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
use App\Entity\StandupMeetingReminder;
use App\Repository\ForecastAccountRepository;
use App\Repository\SlackTeamRepository;
use App\Repository\StandupMeetingReminderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class Handler
{
    private $em;
    private $forecastAccountRepository;
    private $forecastDataSelector;

    /** @var SlackTeamRepository */
    private $slackTeamRepository;
    private $standupMeetingReminderRepository;

    public function __construct(EntityManagerInterface $em, ForecastAccountRepository $forecastAccountRepository, SlackTeamRepository $slackTeamRepository, ForecastDataSelector $forecastDataSelector, StandupMeetingReminderRepository $standupMeetingReminderRepository)
    {
        $this->em = $em;
        $this->forecastAccountRepository = $forecastAccountRepository;
        $this->slackTeamRepository = $slackTeamRepository;
        $this->forecastDataSelector = $forecastDataSelector;
        $this->standupMeetingReminderRepository = $standupMeetingReminderRepository;
    }

    public function handleRequest(Request $request)
    {
        if ('list' === $request->request->get('text')) {
            return $this->listReminders($request);
        }

        return $this->openModal($request);
    }

    public function handleBlockAction(array $payload)
    {
        $slackTeam = $this->slackTeamRepository->findOneBy([
            'teamId' => $payload['team']['id'],
        ]);

        $action = $payload['actions'][0];

        if ('change' === $action['action_id']) {
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
        } elseif ('create' === $action['action_id']) {
            return $this->displayModalForm(
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

    public function handleSubmission(array $payload)
    {
        $selectedProjectForDisplay = [];
        $selectedProjectIds = [];
        $slackTeam = $this->slackTeamRepository->findOneBy([
            'teamId' => $payload['team']['id'],
        ]);
        $privateMetadata = json_decode($payload['view']['private_metadata'], true);

        if (isset($payload['view']['state']['values']['channel']['selected_channel']['selected_channel'])) {
            $channelId = $payload['view']['state']['values']['channel']['selected_channel']['selected_channel'];
        } else {
            $channelId = $privateMetadata['channel_id'];
        }

        foreach ($payload['view']['state']['values']['projects']['selected_projects']['selected_options'] as $project) {
            $selectedProjectForDisplay[] = sprintf('"%s"', $project['text']['text']);
            $selectedProjectIds[] = $project['value'];
        }

        if (\count($selectedProjectForDisplay) > 1) {
            $lastProject = ' and ' . array_pop($selectedProjectForDisplay);
        } else {
            $lastProject = '';
        }

        $selectedProjectForDisplay = implode(', ', $selectedProjectForDisplay) . $lastProject;
        $selectedTime = $payload['view']['state']['values']['time']['selected_time']['selected_option']['value'];
        $standupMeetingReminder = $this->standupMeetingReminderRepository->findOneBy([
            'channelId' => $channelId,
            'slackTeam' => $slackTeam,
        ]);
        $actionName = 'updated';

        if (!$standupMeetingReminder) {
            $standupMeetingReminder = new StandupMeetingReminder();
            $standupMeetingReminder->setChannelId($channelId);
            $standupMeetingReminder->setSlackTeam($slackTeam);
            $actionName = 'created';
        }

        $standupMeetingReminder->setUpdatedBy('@' . $payload['user']['username']);
        $standupMeetingReminder->setIsEnabled(true);
        $standupMeetingReminder->setForecastProjects($selectedProjectIds);
        $standupMeetingReminder->setTime($selectedTime);
        $this->em->persist($standupMeetingReminder);
        $this->em->flush();

        $client = \JoliCode\Slack\ClientFactory::create($slackTeam->getAccessToken());
        $message = sprintf(
            '<@%s> %s a stand-up reminder in this channel. It will run each day at `%s` and ping people working on projects %s.',
            $payload['user']['username'],
            $actionName,
            $selectedTime,
            $selectedProjectForDisplay
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
            $this->sendRemindersList(
                $slackTeam->getTeamId(),
                $payload['trigger_id'],
                $privateMetadata['response_url']
            );
        }

        return new JsonResponse(['response_action' => 'clear']);
    }

    public function listReminders(Request $request)
    {
        $this->sendRemindersList(
            $request->request->get('team_id'),
            $request->request->get('trigger_id'),
            $request->request->get('response_url')
        );
    }

    private function openModal(Request $request)
    {
        return $this->displayModalForm(
            $request->request->get('team_id'),
            $request->request->get('channel_id'),
            $request->request->get('trigger_id')
        );
    }

    private function displayModalForm(string $teamId, string $channelId, string $triggerId, string $responseUrl = null)
    {
        // get the forecast accounts which have a SlackTeam in this organization
        $forecastAccounts = $this->forecastAccountRepository->findBySlackTeamId($teamId);
        $slackTeam = $this->slackTeamRepository->findOneByTeamId($teamId);
        $availableProjects = [];
        $availableTimes = [];
        $initialProjects = [];
        $initialTime = null;
        $blocks = [];
        $privateMetadata = [
            'channel_id' => $channelId,
        ];

        // search if there's already a StandupMeetingReminder in this channel
        $standupMeetingReminder = $this->standupMeetingReminderRepository->findOneBy([
            'channelId' => $channelId,
            'slackTeam' => $slackTeam,
        ]);

        foreach ($forecastAccounts as $forecastAccount) {
            $this->forecastDataSelector->setForecastAccount($forecastAccount);
            $projects = $this->forecastDataSelector->getProjects();

            foreach ($projects as $project) {
                $projectItem = [
                    'text' => [
                        'type' => 'plain_text',
                        'text' => $project->getName(),
                    ],
                    'value' => (string) $project->getId(),
                ];
                $availableProjects[] = $projectItem;

                if ($standupMeetingReminder && \in_array($project->getId(), $standupMeetingReminder->getForecastProjects(), true)) {
                    $initialProjects[] = $projectItem;
                }
            }
        }

        if ($standupMeetingReminder) {
            list($initialHour, $initialMinute) = explode(':', $standupMeetingReminder->getTime());
        } else {
            $initialHour = 10;
            $initialMinute = 0;
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

        if (null !== $responseUrl) {
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
                        'text' => 'Select a channel',
                    ],
                ],
            ];
            $privateMetadata['response_url'] = $responseUrl;
        }

        $blocks[] = [
            'type' => 'input',
            'block_id' => 'projects',
            'label' => [
                'type' => 'plain_text',
                'text' => 'Select one or more Forecast projects',
            ],
            'element' => [
                'type' => 'multi_static_select',
                'action_id' => 'selected_projects',
                'placeholder' => [
                    'type' => 'plain_text',
                    'text' => 'Select projects',
                ],
                'options' => $availableProjects,
            ],
        ];
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
                'private_metadata' => json_encode($privateMetadata),
                'title' => [
                    'type' => 'plain_text',
                    'text' => 'Standup Reminder',
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

        if (\count($initialProjects) > 0) {
            $body['view']['blocks'][0]['element']['initial_options'] = $initialProjects;
        }

        $client = HttpClient::create();
        $client->request('POST', 'https://slack.com/api/views.open', [
            'headers' => [
                'Authorization' => 'Bearer ' . $slackTeam->getAccessToken(),
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($body),
        ]);
    }

    private function sendRemindersList(string $teamId, string $triggerId, string $responseUrl)
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
                    'action_id' => 'change',
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
                    'action_id' => 'create',
                ],
            ],
        ];
        $body = [
            'trigger_id' => $triggerId,
            'blocks' => $reminderBlocks,
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
