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

class SlackDataSelector
{
    private $client;

    public function __construct(SlackClient $slackClient)
    {
        $this->client = $slackClient;
    }

    /**
     * @return \JoliCode\Slack\Api\Model\ObjsConversation[]
     */
    public function getConversations(SlackTeam $slackTeam)
    {
        $this->client->setSlackTeam($slackTeam);

        return $this->client->conversationsList()->getChannels();
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

    /**
     * @return \JoliCode\Slack\Api\Model\ObjsUser[]
     */
    public function getUsers(SlackTeam $slackTeam)
    {
        $this->client->setSlackTeam($slackTeam);

        return $this->client->usersList()->getMembers();
    }

    public function getUsersByEmail(SlackTeam $slackTeam): array
    {
        $users = $this->getUsers($slackTeam);
        $emails = [];

        foreach ($users as $user) {
            if ($user->getProfile()->getEmail()) {
                $emails[$user->getProfile()->getEmail()] = $user->getId();
            }
        }

        return $emails;
    }
}
