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
        'project_directory' => '',
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
    front_build();
    migrate();
    infra\workers_start();

    notify('The stack is now up and running.');
    io()->success('The stack is now up and running.');

    about();
}

#[AsTask(description: 'Installs the application (composer, yarn, ...)', namespace: 'app')]
function install(): void
{
    docker_compose_run('composer install -n --prefer-dist --optimize-autoloader');
    front_install();

    qa\install();
}

#[AsTask(name: 'build', description: 'Build the frontend', namespace: 'app:front')]
function front_build(): void
{
    docker_compose_run('yarn run build');
}

#[AsTask(name: 'install', description: 'Install the frontend dependencies', namespace: 'app:front')]
function front_install(): void
{
    docker_compose_run('yarn');
}

#[AsTask(description: 'Run Yarn watcher', namespace: 'app:front')]
function front_watch(): void
{
    docker_compose_run('yarn run watch');
}

#[AsTask(description: 'Clear the application cache', namespace: 'app')]
function cache_clear(): void
{
    docker_compose_run('rm -rf var/cache/ && bin/console cache:warmup');
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
