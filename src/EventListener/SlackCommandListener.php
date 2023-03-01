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

use App\Entity\SlackRequest;
use App\ForecastReminder\Handler as ForecastReminderHandler;
use App\Harvest\Handler as HarvestTimesheetReminderHandler;
use App\Slack\Sender as SlackSender;
use App\Slack\SignatureComputer;
use App\StandupMeetingReminder\Handler as StandupMeetingReminderHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SlackCommandListener implements EventSubscriberInterface
{
    private ?\App\Entity\SlackRequest $slackRequest = null;

    public function __construct(private readonly EntityManagerInterface $em, private readonly ForecastReminderHandler $forecastReminderHandler, private readonly HarvestTimesheetReminderHandler $harvestTimesheetReminderHandler, private readonly SignatureComputer $signatureComputer, private readonly SlackSender $slackSender, private readonly StandupMeetingReminderHandler $standupMeetingReminderHandler)
    {
    }

    public function onRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        if (str_starts_with((string) $request->attributes->get('_route'), 'slack_')) {
            $timestamp = $request->headers->get('X-Slack-Request-Timestamp', '');
            $signature = $request->headers->get('X-Slack-Signature', '');
            $signatureValid = $signature === $this->signatureComputer->compute($timestamp, $request->getContent());

            $this->slackRequest = new SlackRequest();
            $this->slackRequest->setUrl($request->getRequestUri());
            $this->slackRequest->setRequestPayload($request->request->get('payload'));
            $this->slackRequest->setRequestContent($request->getContent());
            $this->slackRequest->setXSlackRequestTimestamp($timestamp);
            $this->slackRequest->setXSlackSignature($signature);
            $this->slackRequest->setIsSignatureValid($signatureValid);
            $this->em->persist($this->slackRequest);
            $this->em->flush();

            if (!$signatureValid) {
                $event->setResponse(new Response('D\'oh!', \Symfony\Component\HttpFoundation\Response::HTTP_I_AM_A_TEAPOT));
            }
        }
    }

    public function onTerminate(TerminateEvent $event)
    {
        $request = $event->getRequest();

        try {
            if (null !== $this->slackRequest) {
                $this->slackRequest->setResponse($event->getResponse()->__toString());
                $this->em->flush();
            }

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
            $this->slackSender->sendMessage(
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
            KernelEvents::REQUEST => 'onRequest',
            KernelEvents::TERMINATE => 'onTerminate',
        ];
    }
}
