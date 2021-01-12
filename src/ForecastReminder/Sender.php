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

use App\Entity\ForecastAccountSlackTeam;
use App\Entity\ForecastReminder;
use App\Repository\ForecastAccountSlackTeamRepository;
use App\Repository\ForecastReminderRepository;
use Cron\CronExpression;
use Doctrine\ORM\EntityManagerInterface;
use JoliCode\Slack\ClientFactory;
use JoliCode\Slack\Exception\SlackErrorResponse;

class Sender
{
    private $botName;
    private $em;
    private $forecastAccountSlackTeamRepository;
    private $forecastReminderRepository;

    public function __construct(
        EntityManagerInterface $em,
        ForecastAccountSlackTeamRepository $forecastAccountSlackTeamRepository,
        ForecastReminderRepository $forecastReminderRepository)
    {
        $this->em = $em;
        $this->forecastAccountSlackTeamRepository = $forecastAccountSlackTeamRepository;
        $this->forecastReminderRepository = $forecastReminderRepository;
    }

    public function send()
    {
        $this->botName = $this->getFunnyBotName();
        $forecastReminders = $this->forecastReminderRepository->findAll();
        $forecastRemindersCount = 0;

        foreach ($forecastReminders as $forecastReminder) {
            $cron = CronExpression::factory($forecastReminder->getCronExpression());

            if ($cron->isDue()) {
                $this->sendForecastReminder($forecastReminder);
                ++$forecastRemindersCount;
            }
        }

        return $forecastRemindersCount;
    }

    private function sendForecastReminder(ForecastReminder $forecastReminder)
    {
        $forecastAccountSlackTeams = $forecastReminder->getForecastAccount()->getForecastAccountSlackTeams();

        if (\count($forecastAccountSlackTeams) > 0) {
            $builder = new Builder($forecastReminder);
            $message = $builder->buildMessage();

            if (false !== $message) {
                $title = $builder->buildTitle();

                foreach ($forecastAccountSlackTeams as $forecastAccountSlackTeam) {
                    if ($forecastAccountSlackTeam->getChannelId()) {
                        try {
                            $slackClient = ClientFactory::create(
                                $forecastAccountSlackTeam->getSlackTeam()->getAccessToken()
                            );
                            $slackClient->chatPostMessage([
                                'channel' => $forecastAccountSlackTeam->getChannelId(),
                                'username' => $this->botName,
                                'blocks' => json_encode([
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
                                ]),
                            ]);
                            $forecastAccountSlackTeam->errorCount = 0;
                        } catch (SlackErrorResponse $e) {
                            $forecastAccountSlackTeam->errorCount++;

                            if ($forecastAccountSlackTeam->errorCount > ForecastAccountSlackTeam::MAX_ERRORS_ALLOWED) {
                                $this->forecastAccountSlackTeamRepository->remove($forecastAccountSlackTeam);
                            } else {
                                $this->em->persist($forecastAccountSlackTeam);
                            }
                        }
                    }
                }

                $this->em->flush();
            }
        }
    }

    private function getFunnyBotName()
    {
        $adjectives = [
            'adorable',
            'adventurous',
            'aggressive',
            'amused',
            'angry',
            'annoying',
            'anxious',
            'beautiful',
            'bloody',
            'brave',
            'bright',
            'cautious',
            'charming',
            'clumsy',
            'combative',
            'confused',
            'cooperative',
            'courageous',
            'crazy',
            'creepy',
            'cruel',
            'cute',
            'depressed',
            'determined',
            'disgusted',
            'disturbed',
            'doubtful',
            'eager',
            'elegant',
            'embarrassed',
            'encouraging',
            'enthusiastic',
            'evil',
            'faithful',
            'famous',
            'fantastic',
            'foolish',
            'friendly',
            'gentle',
            'glorious',
            'grumpy',
            'happy',
            'innocent',
            'lazy',
            'lovely',
            'magnificent',
            'mysterious',
            'nervous',
            'perfect',
            'pleasant',
            'proud',
            'shiny',
            'silly',
            'smiling',
            'sparkling',
            'splendid',
            'strange',
            'stupid',
            'talented',
            'tender',
            'troubled',
            'ugly',
            'vivacious',
            'wild',
            'worried',
            'zealous',
        ];
        $nouns = [
            'almost-human',
            'automat',
            'droid',
            'bot',
            'better-than-a-CHO thing',
            'robot',
        ];

        return sprintf('The %s Forecast %s', $adjectives[array_rand($adjectives)], $nouns[array_rand($nouns)]);
    }
}
