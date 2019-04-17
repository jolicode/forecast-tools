<?php

declare(strict_types=1);

/*
 * This file is part of JoliCode's Forecast Tools project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20190417114053 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'migrate data from slackWebHook to slackWebHooks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE `forecast_alert` SET `slack_web_hooks`=CONCAT("a:1:{i:1;s:", LENGTH(`slack_web_hook`), ":\"", `slack_web_hook`, "\";}")');
    }

    public function down(Schema $schema): void
    {
    }
}
