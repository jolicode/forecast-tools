<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Alert;

use App\Entity\ForecastAlert;
use App\Notification\SlackNotifier;
use App\Repository\ForecastAlertRepository;
use Cron\CronExpression;

class Sender
{
    private $forecastAlertRepository;
    private $slackNotifier;

    public function __construct(ForecastAlertRepository $forecastAlertRepository, SlackNotifier $slackNotifier)
    {
        $this->forecastAlertRepository = $forecastAlertRepository;
        $this->slackNotifier = $slackNotifier;
    }

    public function send()
    {
        $alerts = $this->forecastAlertRepository->findAll();
        $alertsCount = 0;

        foreach ($alerts as $alert) {
            $cron = CronExpression::factory($alert->getCronExpression());

            if ($cron->isDue()) {
                $this->sendAlert($alert);
                ++$alertsCount;
            }
        }

        return $alertsCount;
    }

    private function sendAlert(ForecastAlert $alert)
    {
        $builder = new Builder($alert);
        $message = $builder->buildMessage();
        $title = $builder->buildTitle();

        foreach ($alert->getSlackWebHooks() as $slackWebHook)
            $this->slackNotifier->notify(
                $title,
                $message,
                $slackWebHook
            );
        }
    }
}
