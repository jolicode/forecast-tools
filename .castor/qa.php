<?php

namespace qa;

use Castor\Attribute\AsOption;
use Castor\Attribute\AsTask;
use Symfony\Component\Console\Input\InputOption;

use function Castor\get_context;

#[AsTask(description: 'Runs all QA tasks')]
function all(): void
{
    install();
    cs();
    phpstan();
    rector();
    twig_lint();
    yaml_lint();
}

#[AsTask(description: 'Installs tooling')]
function install(): void
{
    docker_compose_run('composer install -o', workDir: '/home/app/root/tools/php-cs-fixer');
    docker_compose_run('composer install -o', workDir: '/home/app/root/tools/phpstan');
    docker_compose_run('composer install -o', workDir: '/home/app/root/tools/rector');
}

#[AsTask(description: 'Fix coding standards')]
function cs(
    #[AsOption(name: 'dry-run', description: 'Do not make changes and outputs diff', mode: InputOption::VALUE_NONE)]
    ?bool $dryRun = null,
): int {
    $command = 'php-cs-fixer fix';

    if ($dryRun === true) {
        $command .= ' --dry-run --diff';
    }

    $c = get_context()
        ->withAllowFailure(true)
    ;

    return docker_compose_run($command, c: $c, workDir: '/home/app/root')->getExitCode();
}

#[AsTask(description: 'Run the phpstan analysis')]
function phpstan(): int
{
    return docker_compose_run('phpstan --configuration=/home/app/root/phpstan.neon analyse', workDir: '/home/app/root')->getExitCode();
}

#[AsTask(description: 'Run the rector upgrade')]
function rector(): int
{
    return docker_compose_run('rector process', workDir: '/home/app/root')->getExitCode();
}

#[AsTask(name: 'twig-lint', description: 'Lint twig files')]
function twig_lint(): int
{
    return docker_compose_run('bin/console lint:twig --show-deprecations templates', workDir: '/home/app/root')->getExitCode();
}

#[AsTask(name: 'yaml-lint', description: 'Lint YAML files')]
function yaml_lint(): int
{
    return docker_compose_run('bin/console lint:yaml config', workDir: '/home/app/root')->getExitCode();
}
