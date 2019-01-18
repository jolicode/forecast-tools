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

use App\Alert\Sender;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ForecastAlertSendCommand extends Command
{
    protected static $defaultName = 'forecast:alert-send';
    private $alertSender;

    public function __construct(Sender $alertSender)
    {
        $this->alertSender = $alertSender;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Sends Forecast Slack alerts')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $count = $this->alertSender->send();

        $io->success(sprintf('Sent %s alerts', $count));
    }
}
