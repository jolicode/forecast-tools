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

use App\StandupMeetingReminder\Sender;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class StandupMeetingReminderSendCommand extends Command
{
    protected static $defaultName = 'forecast:standup-meeting-reminder-send';
    private $standupMeetingReminderSender;

    public function __construct(Sender $standupMeetingReminderSender)
    {
        $this->standupMeetingReminderSender = $standupMeetingReminderSender;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Sends standup meeting Reminders');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $count = $this->standupMeetingReminderSender->send();

        $io->success(sprintf('Sent %s reminders', $count));

        return 0;
    }
}
