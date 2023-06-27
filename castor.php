<?php

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Castor\Attribute\AsTask;

use function Castor\import;
use function Castor\io;
use function Castor\notify;
use function Castor\variable;

import(__DIR__ . '/.castor');

/**
 * @return array<string, mixed>
 */
function create_default_variables(): array
{
    return [
        'project_name' => 'forecast-tools',
        'root_domain' => 'local.forecast.jolicode.com',
        'extra_domains' => [],
        'project_directory' => '.',
        'php_version' => $_SERVER['DS_PHP_VERSION'] ?? '8.2',
    ];
}

#[AsTask(description: 'Builds and starts the infrastructure, then install the application (composer, yarn, ...)')]
function start(): void
{
    infra\workers_stop();
    infra\generate_certificates(false);
    infra\build();
    infra\up();
    cache_clear();
    install();
    build_front();
    migrate();
    infra\workers_start();

    notify('The stack is now up and running.');
    io()->success('The stack is now up and running.');

    about();
}

#[AsTask(description: 'Installs the application (composer, yarn, ...)', namespace: 'app')]
function install(): void
{
    $basePath = sprintf('%s/%s', variable('root_dir'), variable('project_directory'));

    if (is_file("{$basePath}/composer.json")) {
        docker_compose_run('composer install -n --prefer-dist --optimize-autoloader');
    }
    if (is_file("{$basePath}/yarn.lock")) {
        docker_compose_run('yarn');
    } elseif (is_file("{$basePath}/package.json")) {
        docker_compose_run('npm install');
    }
}

#[AsTask(name: 'build-front', description: 'Build the frontend', namespace: 'app')]
function build_front(): void
{
    docker_compose_run('yarn run build');
}

#[AsTask(description: 'Clear the application cache', namespace: 'app')]
function cache_clear(): void
{
    docker_compose_run('rm -rf var/cache/ && bin/console cache:warmup');
}

#[AsTask(description: 'Fix coding standards', namespace: 'qa')]
function cs(): void
{
    docker_compose_run('php ./vendor/bin/php-cs-fixer fix');
}

#[AsTask(description: 'Migrates database schema', namespace: 'app:db')]
function migrate(): void
{
    docker_compose_run('bin/console doctrine:database:create --if-not-exists');
    docker_compose_run('bin/console doctrine:migration:migrate -n --allow-no-migration');
}

#[AsTask(description: 'Generate new migration', namespace: 'app:db')]
function migration(): void
{
    docker_compose_run('bin/console make:migration');
}

#[AsTask(description: 'Run the phpstan analysis', namespace: 'qa')]
function phpstan(): void
{
    docker_compose_run('php ./vendor/bin/phpstan analyse');
}

#[AsTask(description: 'Run the rector upgrade', namespace: 'qa')]
function rector(): void
{
    docker_compose_run('php ./vendor/bin/rector process');
}

#[AsTask(name: 'twig-lint', description: 'Lint twig files', namespace: 'qa')]
function twig_lint(): void
{
    docker_compose_run('bin/console lint:twig --show-deprecations templates');
}

#[AsTask(description: 'Run Yarn watcher', namespace: 'app')]
function watch(): void
{
    docker_compose_run('yarn run watch');
}
