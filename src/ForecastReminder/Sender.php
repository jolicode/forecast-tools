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

use App\Entity\ForecastReminder;
use App\Notification\SlackNotifier;
use App\Repository\ForecastReminderRepository;
use Cron\CronExpression;

class Sender
{
    private $forecastReminderRepository;
    private $slackNotifier;

    public function __construct(ForecastReminderRepository $forecastReminderRepository, SlackNotifier $slackNotifier)
    {
        $this->forecastReminderRepository = $forecastReminderRepository;
        $this->slackNotifier = $slackNotifier;
    }

    public function send()
    {
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
        $slackChannels = $forecastReminder->getForecastAccount()->getSlackChannels();

        if (\count($slackChannels) > 0) {
            $builder = new Builder($forecastReminder);
            $message = $builder->buildMessage();
            $title = $builder->buildTitle();

            foreach ($slackChannels as $slackChannel) {
                $this->slackNotifier->notify(
                    $title,
                    $message,
                    $slackChannel->getWebhookUrl()
                );
            }
        }
    }
}
