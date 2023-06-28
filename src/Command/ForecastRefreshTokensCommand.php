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

use App\Security\HarvestTokenRefresher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'forecast:refresh-tokens',
    description: 'Refresh Forecast tokens',
)]
class ForecastRefreshTokensCommand extends Command
{
    public function __construct(private readonly HarvestTokenRefresher $refresher)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $refreshed = $this->refresher->refresh();
        $io->success(sprintf('Refreshed %s tokens. %s errors occurred.', $refreshed[0], $refreshed[1]));

        return Command::SUCCESS;
    }
}
