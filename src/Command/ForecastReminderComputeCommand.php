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

use App\ForecastReminder\Builder;
use App\Repository\ForecastReminderRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ForecastReminderComputeCommand extends Command
{
    protected static $defaultName = 'forecast:reminder-compute';
    private $forecastReminderRepository;

    public function __construct(ForecastReminderRepository $forecastReminderRepository)
    {
        $this->forecastReminderRepository = $forecastReminderRepository;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Sends Forecast Reminders')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $forecastReminder = $this->forecastReminderRepository->findOneById(1);
        $builder = new Builder($forecastReminder);
        $message = $builder->buildMessage();

        dump($message);

        return 0;
    }
}
