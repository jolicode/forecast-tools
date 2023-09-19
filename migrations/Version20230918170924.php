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

final class Version20230918170924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add a way to attach standup reminders to a client, not only to projects';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE standup_meeting_reminder ADD forecast_clients LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('UPDATE standup_meeting_reminder SET forecast_clients="a:0:{}"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE standup_meeting_reminder DROP forecast_clients');
    }
}
