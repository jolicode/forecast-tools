<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventListener;

use App\ForecastReminder\Handler as ForecastReminderHandler;
use App\Harvest\Handler as HarvestTimesheetReminderHandler;
use App\Slack\Sender as SlackSender;
use App\StandupMeetingReminder\Handler as StandupMeetingReminderHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SlackCommandListener implements EventSubscriberInterface
{
    private ForecastReminderHandler $forecastReminderHandler;
    private HarvestTimesheetReminderHandler $harvestTimesheetReminderHandler;
    private SlackSender $slackSender;
    private StandupMeetingReminderHandler $standupMeetingReminderHandler;

    public function __construct(ForecastReminderHandler $forecastReminderHandler, HarvestTimesheetReminderHandler $harvestTimesheetReminderHandler, SlackSender $slackSender, StandupMeetingReminderHandler $standupMeetingReminderHandler)
    {
        $this->forecastReminderHandler = $forecastReminderHandler;
        $this->harvestTimesheetReminderHandler = $harvestTimesheetReminderHandler;
        $this->slackSender = $slackSender;
        $this->standupMeetingReminderHandler = $standupMeetingReminderHandler;
    }

    public function onTerminate(TerminateEvent $event)
    {
        $request = $event->getRequest();

        try {
            if ('slack_command' === $request->attributes->get('_route')) {
                if (ForecastReminderHandler::SLACK_COMMAND_NAME === $request->request->get('command')) {
                    $this->forecastReminderHandler->handleRequest($request);
                } elseif (StandupMeetingReminderHandler::SLACK_COMMAND_NAME === $request->request->get('command')) {
                    $this->standupMeetingReminderHandler->handleRequest($request);
                } elseif (HarvestTimesheetReminderHandler::SLACK_COMMAND_NAME === $request->request->get('command')) {
                    $this->harvestTimesheetReminderHandler->handleRequest($request);
                }
            }
        } catch (\DomainException $e) {
            $this->slackSender->send(
                $request->request->get('response_url'),
                $request->request->get('trigger_id'),
                $e->getMessage(),
                true
            );
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::TERMINATE => 'onTerminate',
        ];
    }
}
