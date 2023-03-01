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

use App\Entity\ForecastAccount;
use App\Entity\ForecastAccountSlackTeam;
use App\Entity\SlackTeam;
use App\Form\DeleteAccountFormType;
use App\Form\ForecastSettingsType;
use App\Form\HarvestSettingsType;
use App\Form\HarvestTimesheetsReminderType;
use App\Form\UserSettingsType;
use App\Harvest\Reminder;
use App\Repository\ForecastAccountSlackTeamRepository;
use App\Repository\SlackTeamRepository;
use App\Repository\UserForecastAccountRepository;
use App\Repository\UserRepository;
use App\Security\Provider\Slack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/{slug}/settings', name: 'organization_settings_', defaults: ['menu' => 'settings'])]
class SettingsController extends AbstractController
{
    final public const SESSION_STATE_KEY = 'slack.state';

    public function __construct(private readonly string $slackClientId, private readonly string $slackClientSecret, private readonly RouterInterface $router)
    {
    }

    #[Route(path: '/account', name: 'account')]
    public function account(Request $request, ForecastAccount $forecastAccount, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $user = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $form = $this->createForm(UserSettingsType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('organization_settings_account', ['slug' => $forecastAccount->getSlug()]);
        }

        return $this->render('organization/settings/account.html.twig', [
            'forecastAccount' => $forecastAccount,
            'form' => $form,
        ]);
    }

    #[Route(path: '/account/delete', name: 'delete_account')]
    public function deleteAccount(Request $request, ForecastAccount $forecastAccount, EntityManagerInterface $em, UserRepository $userRepository, UserForecastAccountRepository $userForecastAccountRepository): Response
    {
        $user = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $forecastAccountsToDelete = $userForecastAccountRepository->findForecastAccountsWithoutOtherAdmin($user);
        $form = $this->createForm(DeleteAccountFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // delete the forecast accounts
            foreach ($forecastAccountsToDelete as $forecastAccountToDelete) {
                $em->remove($forecastAccountToDelete);
            }

            // delete the account
            $em->remove($user);
            $em->flush();

            return $this->redirectToRoute('logout');
        }

        return $this->render('organization/settings/delete_account.html.twig', [
            'forecastAccount' => $forecastAccount,
            'form' => $form,
            'forecastAccountsToDelete' => $forecastAccountsToDelete,
        ]);
    }

    #[Route(path: '/forecast', name: 'forecast')]
    #[IsGranted('admin', subject: 'forecastAccount')]
    public function forecast(Request $request, ForecastAccount $forecastAccount, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ForecastSettingsType::class, $forecastAccount);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $forecastAccount = $form->getData();
            $em->persist($forecastAccount);
            $em->flush();

            return $this->redirectToRoute('organization_settings_forecast', ['slug' => $forecastAccount->getSlug()]);
        }

        return $this->render('organization/settings/forecast.html.twig', [
            'forecastAccount' => $forecastAccount,
            'form' => $form,
        ]);
    }

    #[Route(path: '/harvest', name: 'harvest')]
    #[IsGranted('admin', subject: 'forecastAccount')]
    #[IsGranted('harvest_admin', subject: 'forecastAccount')]
    public function harvest(Request $request, ForecastAccount $forecastAccount, EntityManagerInterface $em): Response
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
            'form' => $form,
        ]);
    }

    #[Route(path: '/harvest-timesheets-reminder', name: 'harvest_timesheets_reminder')]
    #[IsGranted('admin', subject: 'forecastAccount')]
    #[IsGranted('harvest_admin', subject: 'forecastAccount')]
    public function harvestTimesheetsReminder(Request $request, ForecastAccount $forecastAccount, EntityManagerInterface $em, Reminder $harvestReminder): Response
    {
        $form = $this->createForm(HarvestTimesheetsReminderType::class, $forecastAccount->getHarvestAccount());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $harvestAccount = $form->getData();
            $em->persist($harvestAccount);
            $em->flush();

            return $this->redirectToRoute('organization_settings_harvest_timesheets_reminder', ['slug' => $forecastAccount->getSlug()]);
        }

        return $this->render('organization/settings/harvest_timesheets_reminder.html.twig', [
            'forecastAccount' => $forecastAccount,
            'form' => $form,
            'issues' => $harvestReminder->buildForHarvestAccount($forecastAccount->getHarvestAccount()),
        ]);
    }

    #[Route(path: '/slack', name: 'slack')]
    #[IsGranted('admin', subject: 'forecastAccount')]
    public function slack(Request $request, ForecastAccount $forecastAccount, EntityManagerInterface $em, UserRepository $userRepository, SlackTeamRepository $slackTeamRepository, ForecastAccountSlackTeamRepository $forecastAccountSlackTeamRepository): Response
    {
        if ($request->query->has('code')) {
            $session = $request->getSession();

            if (null === $request->query->get('state', null) || ($request->query->get('state') !== $session->get(self::SESSION_STATE_KEY))) {
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
                $slackTeam = $slackTeamRepository->findOneBy([
                    'teamId' => $values['team']['id'],
                ]);

                if (null === $slackTeam) {
                    $slackTeam = new SlackTeam();
                    $slackTeam->setTeamId($values['team']['id']);
                }

                $forecastAccountSlackTeam = $forecastAccountSlackTeamRepository->findOneBy([
                    'forecastAccount' => $forecastAccount,
                    'slackTeam' => $slackTeam,
                ]);

                if (null === $forecastAccountSlackTeam) {
                    $forecastAccountSlackTeam = new ForecastAccountSlackTeam();
                    $forecastAccountSlackTeam->setForecastAccount($forecastAccount);
                    $forecastAccountSlackTeam->setSlackTeam($slackTeam);
                }

                $user = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
                $slackTeam->setAccessToken($token->getToken());
                $slackTeam->setTeamName($values['team']['name']);
                $forecastAccountSlackTeam->setUpdatedBy($user);
                $em->persist($slackTeam);
                $em->persist($forecastAccountSlackTeam);
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

    #[Route(path: '/slack/delete/{forecastAccountSlackTeamId}', name: 'slack_delete')]
    #[IsGranted('admin', subject: 'forecastAccount')]
    public function slackDelete(
        ForecastAccount $forecastAccount,
        #[MapEntity(id: 'forecastAccountSlackTeamId')] ForecastAccountSlackTeam $forecastAccountSlackTeam,
        ForecastAccountSlackTeamRepository $forecastAccountSlackTeamRepository,
        EntityManagerInterface $em): Response
    {
        if ($forecastAccountSlackTeam->getForecastAccount() !== $forecastAccount) {
            throw new NotFoundHttpException('Could not find this Slack channel.');
        }

        $forecastAccountSlackTeamRepository->remove($forecastAccountSlackTeam);
        $em->flush();

        return new RedirectResponse($this->router->generate('organization_settings_slack', [
            'slug' => $forecastAccount->getSlug(),
        ]));
    }

    #[Route(path: '/slack/install', name: 'slack_install')]
    #[IsGranted('admin', subject: 'forecastAccount')]
    public function slackInstall(Request $request, ForecastAccount $forecastAccount): Response
    {
        $provider = $this->getSlackProvider($forecastAccount);

        // build the Slack url
        $options = [
            'scope' => [
                'channels:read',
                'chat:write',
                'chat:write.customize',
                'chat:write.public',
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
