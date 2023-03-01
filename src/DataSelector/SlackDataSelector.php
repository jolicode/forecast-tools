<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\DataSelector;

use App\Client\SlackClient;
use App\Entity\SlackTeam;
use JoliCode\Slack\Api\Model\ObjsConversation;
use JoliCode\Slack\Api\Model\ObjsUserProfile;

class SlackDataSelector
{
    public function __construct(private readonly SlackClient $client)
    {
    }

    public function getConversationInfos(SlackTeam $slackTeam, string $channelId): ?ObjsConversation
    {
        $this->client->setSlackTeam($slackTeam);

        $conversationsInfo = $this->client->conversationsInfo([
            'channel' => $channelId,
        ]);

        return null !== $conversationsInfo ? $conversationsInfo->getChannel() : [];
    }

    /**
     * @return \JoliCode\Slack\Api\Model\ObjsConversation[]
     */
    public function getConversations(SlackTeam $slackTeam): array
    {
        $this->client->setSlackTeam($slackTeam);
        $conversationsList = $this->client->conversationsList([
            'exclude_archived' => true,
        ]);

        return null !== $conversationsList ? $conversationsList->getChannels() : [];
    }

    public function getConversationsForChoice(SlackTeam $slackTeam): array
    {
        $conversations = $this->getConversations($slackTeam);
        $choices = [];

        foreach ($conversations as $conversation) {
            $choices['#' . $conversation->getName()] = $conversation->getId();
        }

        ksort($choices);

        return $choices;
    }

    public function getUserProfile(SlackTeam $slackTeam, string $userId): ObjsUserProfile
    {
        $this->client->setSlackTeam($slackTeam);
        $usersInfo = $this->client->usersInfo([
            'user' => $userId,
        ]);

        return null !== $usersInfo ? $usersInfo->getUser()->getProfile() : new ObjsUserProfile();
    }

    /**
     * @return \JoliCode\Slack\Api\Model\ObjsUser[]
     */
    public function getUsers(SlackTeam $slackTeam): array
    {
        $this->client->setSlackTeam($slackTeam);
        $usersList = $this->client->usersList();

        return null !== $usersList ? $usersList->getMembers() : [];
    }

    /**
     * @return \JoliCode\Slack\Api\Model\ObjsUser[]
     */
    public function getUsersByEmail(SlackTeam $slackTeam): array
    {
        $users = $this->getUsers($slackTeam);
        $emails = [];

        foreach ($users as $user) {
            if (null !== $user->getProfile()->getEmail()) {
                $emails[$user->getProfile()->getEmail()] = $user;
            }
        }

        return $emails;
    }

    /**
     * @return string[]
     */
    public function getUserIdsByEmail(SlackTeam $slackTeam): array
    {
        $users = $this->getUsers($slackTeam);
        $emails = [];

        foreach ($users as $user) {
            if (null !== $user->getProfile()->getEmail()) {
                $emails[$user->getProfile()->getEmail()] = $user->getId();
            }
        }

        return $emails;
    }
}
