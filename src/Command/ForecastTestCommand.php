<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\ForecastReminder\Handler as ForecastReminderHandler;
use App\Repository\ForecastReminderRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ForecastTestCommand extends Command
{
    protected static $defaultName = 'forecast:test';

    private ForecastReminderHandler $forecastReminderHandler;
    private ForecastReminderRepository $forecastReminderRepository;

    public function __construct(ForecastReminderHandler $forecastReminderHandler, ForecastReminderRepository $forecastReminderRepository)
    {
        $this->forecastReminderHandler = $forecastReminderHandler;
        $this->forecastReminderRepository = $forecastReminderRepository;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('slackTeamId', InputArgument::REQUIRED, 'Slack team id')
            ->addArgument('text', InputArgument::OPTIONAL, 'Date for which to fetch the forecast', 'today')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $forecastReminders = $this->forecastReminderRepository->findByTeamId(
            $input->getArgument('slackTeamId')
        );

        if (0 < \count($forecastReminders)) {
            foreach ($forecastReminders as $forecastReminder) {
                $body = $this->forecastReminderHandler->buildReminder($forecastReminder, $input->getArgument('text'));
                dump($body);
            }
        }

        return Command::SUCCESS;
    }
}
