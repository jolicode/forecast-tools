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

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'forecast:promote-admin',
    description: 'Promote or demotes a user to the super admin role.',
)]
class ForecastPromoteAdminCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly UserRepository $userRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User\'s email')
            ->addOption('demote', null, InputOption::VALUE_NONE, 'Remove the super admin role from this user')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $user = $this->userRepository->findOneByEmail($input->getArgument('email'));

        if (null !== $user) {
            $user->setIsSuperAdmin(null !== $input->getOption('demote'));
            $this->em->persist($user);
            $this->em->flush();
            $io->success(sprintf(
                'The user with email %s has been %s as super admin',
                $user->getEmail(),
                (null !== $input->getOption('demote')) ? 'demoted' : 'promoted'
            ));
        } else {
            $io->error(sprintf(
                'Could not find a user with email "%s". Please authenticate first using the application.',
                $input->getArgument('email')
            ));
        }

        return Command::SUCCESS;
    }
}
