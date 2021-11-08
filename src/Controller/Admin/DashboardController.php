<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller\Admin;

use App\Entity\ForecastAccount;
use App\Entity\ForecastReminder;
use App\Entity\HarvestAccount;
use App\Entity\PublicForecast;
use App\Entity\SlackCall;
use App\Entity\SlackRequest;
use App\Entity\StandupMeetingReminder;
use App\Entity\User;
use App\Entity\UserForecastAccount;
use App\Entity\UserHarvestAccount;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    /**
     * @Route("/_admin", name="admin")
     */
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Forecast tools');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::section('Organizations');
        yield MenuItem::linkToCrud('Forecast organizations', 'fa fa-cloud-sun', ForecastAccount::class);
        yield MenuItem::linkToCrud('Harvest organizations', 'fa fa-tractor', HarvestAccount::class);
        yield MenuItem::section('Accounts');
        yield MenuItem::linkToCrud('Users', 'fa fa-users', User::class);
        yield MenuItem::linkToCrud('Forecast accounts', 'fa fa-cloud-sun', UserForecastAccount::class);
        yield MenuItem::linkToCrud('Harvest accounts', 'fa fa-tractor', UserHarvestAccount::class);
        yield MenuItem::section('Features');
        yield MenuItem::linkToCrud('Forecast reminders', 'fa fa-bell', ForecastReminder::class);
        yield MenuItem::linkToCrud('Public forecasts', 'fa fa-external-link-alt', PublicForecast::class);
        yield MenuItem::linkToCrud('Standup reminders', 'fa fa-microphone-alt', StandupMeetingReminder::class);
        yield MenuItem::section('Debug');
        yield MenuItem::linkToCrud('Slack calls', 'fa fa-bug', SlackCall::class);
        yield MenuItem::linkToCrud('Slack requests', 'fa fa-bug', SlackRequest::class);
    }
}
