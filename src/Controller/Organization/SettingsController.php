<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Organization;

use AdamPaterson\OAuth2\Client\Provider\Slack;
use App\Entity\ForecastAccount;
use App\Entity\SlackChannel;
use App\Form\HarvestSettingsType;
use App\Repository\SlackChannelRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * @Route("/{slug}/settings", name="organization_settings_", defaults={"menu": "settings"})
 */
class SettingsController extends AbstractController
{
    const SESSION_STATE_KEY = 'slack.state';

    private $slackClientId;
    private $slackClientSecret;
    private $router;

    public function __construct(string $slackClientId, string $slackClientSecret, RouterInterface $router)
    {
        $this->router = $router;
        $this->slackClientId = $slackClientId;
        $this->slackClientSecret = $slackClientSecret;
    }

    /**
     * @Route("/harvest", name="harvest")
     */
    public function harvest(Request $request, ForecastAccount $forecastAccount, EntityManagerInterface $em)
    {
        $form = $this->createForm(HarvestSettingsType::class, $forecastAccount->getHarvestAccount());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $harvestAccount = $form->getData();
            $em->persist($harvestAccount);
            $em->flush();

            return $this->redirectToRoute('organization_settings_harvest', ['slug' => $forecastAccount->getSlug()]);
        }

        return $this->render('organization/settings/harvest.html.twig', [
            'forecastAccount' => $forecastAccount,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/slack", name="slack")
     */
    public function slack(Request $request, ForecastAccount $forecastAccount, EntityManagerInterface $em, UserRepository $userRepository, SlackChannelRepository $slackChannelRepository)
    {
        if ($request->query->has('code')) {
            $session = $request->getSession();

            if (empty($request->query->get('state')) || ($request->query->get('state') !== $session->get(self::SESSION_STATE_KEY))) {
                $session->remove(self::SESSION_STATE_KEY);

                throw new \RuntimeException('Invalid OAuth state.');
            }

            try {
                // Try to get an access token (using the authorization code grant)
                $provider = $this->getSlackProvider($forecastAccount);
                $token = $provider->getAccessToken('authorization_code', [
                    'code' => $request->query->get('code'),
                ]);
                $values = $token->getValues();

                $slackChannel = $slackChannelRepository->findOneBy([
                    'teamId' => $values['team_id'],
                    'forecastAccount' => $forecastAccount,
                ]);

                if (!$slackChannel) {
                    $slackChannel = new SlackChannel();
                    $slackChannel->setForecastAccount($forecastAccount);
                    $slackChannel->setTeamId($values['team_id']);
                }

                $user = $userRepository->findOneBy(['email' => $this->getUser()->getUsername()]);
                $slackChannel->setWebhookConfigurationUrl($values['incoming_webhook']['configuration_url']);
                $slackChannel->setAccessToken($token->getToken());
                $slackChannel->setUpdatedBy($user);
                $slackChannel->setWebhookUrl($values['incoming_webhook']['url']);
                $slackChannel->setWebhookChannelId($values['incoming_webhook']['channel_id']);
                $slackChannel->setWebhookChannel($values['incoming_webhook']['channel']);
                $slackChannel->setTeamName($values['team_name']);
                $em->persist($slackChannel);
                $em->flush();

                return new RedirectResponse($this->router->generate('organization_settings_slack', [
                    'slug' => $forecastAccount->getSlug(),
                ]));
            } catch (\Exception $e) {
                throw new \RuntimeException('Failed to retrieve data from Slack.', 0, $e);
            }
        }

        return $this->render('organization/settings/slack.html.twig', [
            'forecastAccount' => $forecastAccount,
        ]);
    }

    /**
     * @Route("/slack/delete/{slackChanneId}", name="slack_delete")
     * @ParamConverter("slackChannel", options={"id" = "slackChanneId"})
     */
    public function slackDelete(ForecastAccount $forecastAccount, SlackChannel $slackChannel, SlackChannelRepository $slackChannelRepository)
    {
        if ($slackChannel->getForecastAccount() !== $forecastAccount) {
            throw new NotFoundHttpException('Could not find this Slack channel.');
        }

        // if this is the last slackChannel with this workspace teamId, uninstall the app from the workspace
        if (1 === $slackChannelRepository->countByTeamId($slackChannel->getTeamId())) {
            // @TODO once slack-php-api releases a version using jane 5
            // call https://api.slack.com/methods/apps.uninstall
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($slackChannel);
        $entityManager->flush();

        return new RedirectResponse($this->router->generate('organization_settings_slack', [
            'slug' => $forecastAccount->getSlug(),
        ]));
    }

    /**
     * @Route("/slack/install", name="slack_install")
     */
    public function slackInstall(Request $request, ForecastAccount $forecastAccount, UserRepository $userRepository, SlackChannelRepository $slackChannelRepository)
    {
        $provider = $this->getSlackProvider($forecastAccount);

        // build the Slack url
        $options = [
            'scope' => [
                'incoming-webhook',
                'commands',
                'users:read',
                'users:read.email',
            ],
        ];
        $authUrl = $provider->getAuthorizationUrl($options);

        // strore the state in session
        $state = $provider->getState();
        $session = $request->getSession();
        $session->set(self::SESSION_STATE_KEY, $state);

        return new RedirectResponse($authUrl);
    }

    private function getSlackProvider(ForecastAccount $forecastAccount)
    {
        return new Slack([
            'clientId' => $this->slackClientId,
            'clientSecret' => $this->slackClientSecret,
            'redirectUri' => $this->router->generate('organization_settings_slack', ['slug' => $forecastAccount->getSlug()], RouterInterface::ABSOLUTE_URL),
        ]);
    }
}
