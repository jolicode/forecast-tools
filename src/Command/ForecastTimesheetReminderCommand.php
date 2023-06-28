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

use App\Harvest\Reminder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'forecast:timesheet-reminder',
    description: 'Send timesheet reminders, on the first worked day of each month',
)]
class ForecastTimesheetReminderCommand extends Command
{
    public function __construct(private readonly Reminder $timesheetReminder)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = $this->timesheetReminder->send();
        $io->success(sprintf('Sent %s reminders', $count));

        return Command::SUCCESS;
    }
}
