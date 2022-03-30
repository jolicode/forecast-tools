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

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200818011033 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE harvest_account ADD timesheet_reminder_slack_team_id INT DEFAULT NULL, ADD do_not_send_timesheet_reminder_for LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE harvest_account ADD CONSTRAINT FK_A787F78F207F0753 FOREIGN KEY (timesheet_reminder_slack_team_id) REFERENCES forecast_account_slack_team (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_A787F78F207F0753 ON harvest_account (timesheet_reminder_slack_team_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE harvest_account DROP FOREIGN KEY FK_A787F78F207F0753');
        $this->addSql('DROP INDEX IDX_A787F78F207F0753 ON harvest_account');
        $this->addSql('ALTER TABLE harvest_account DROP timesheet_reminder_slack_team_id, DROP do_not_send_timesheet_reminder_for');
    }
}
