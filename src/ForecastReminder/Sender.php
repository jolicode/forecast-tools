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
use Bugsnag\Client;
use Cron\CronExpression;
use Doctrine\ORM\EntityManagerInterface;
use JoliCode\Slack\ClientFactory;
use JoliCode\Slack\Exception\SlackErrorResponse;

class Sender
{
    private readonly string $botName;

    public function __construct(
        private readonly Builder $builder,
        private readonly EntityManagerInterface $em,
        private readonly ForecastAccountSlackTeamRepository $forecastAccountSlackTeamRepository,
        private readonly ForecastReminderRepository $forecastReminderRepository,
        private readonly Client $bugsnagClient,
    ) {
        $this->botName = $this->getFunnyBotName();
    }

    public function send(): int
    {
        $forecastReminders = $this->forecastReminderRepository->findAll();
        $forecastRemindersCount = 0;

        foreach ($forecastReminders as $forecastReminder) {
            $cron = new CronExpression($forecastReminder->getCronExpression());

            if ($cron->isDue()) {
                try {
                    $this->sendForecastReminder($forecastReminder);
                } catch (\Exception $e) {
                    $this->bugsnagClient->notifyException($e, function ($report) use ($forecastReminder): void {
                        $report->setMetaData([
                            'forecastReminder' => $forecastReminder->getId(),
                            'forecastAccount' => $forecastReminder->getForecastAccount()->getName(),
                        ]);
                    });
                }

                ++$forecastRemindersCount;
            }
        }

        return $forecastRemindersCount;
    }

    private function sendForecastReminder(ForecastReminder $forecastReminder): void
    {
        $forecastAccountSlackTeams = $forecastReminder->getForecastAccount()->getForecastAccountSlackTeams();

        if (\count($forecastAccountSlackTeams) > 0) {
            $payload = $this->builder->buildBlocks($forecastReminder);

            if (true === $payload['successful']) {
                foreach ($forecastAccountSlackTeams as $forecastAccountSlackTeam) {
                    if (null !== $forecastAccountSlackTeam->getChannelId()) {
                        try {
                            $slackClient = ClientFactory::create(
                                $forecastAccountSlackTeam->getSlackTeam()->getAccessToken()
                            );
                            $slackClient->chatPostMessage([
                                'channel' => $forecastAccountSlackTeam->getChannelId(),
                                'username' => $this->botName,
                                'blocks' => json_encode($payload['blocks'], \JSON_THROW_ON_ERROR),
                            ]);
                            $forecastAccountSlackTeam->setErrorsCount(0);
                            $forecastReminder->setLastTimeSentAt(new \DateTime());
                            $this->em->persist($forecastReminder);
                        } catch (SlackErrorResponse) {
                            $forecastAccountSlackTeam->increaseErrorsCount();

                            if ($forecastAccountSlackTeam->getErrorsCount() > ForecastAccountSlackTeam::MAX_ERRORS_ALLOWED) {
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

    private function getFunnyBotName(): string
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
