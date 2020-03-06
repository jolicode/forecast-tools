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

final class Version20200305090747 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE standup_meeting_reminder (id INT AUTO_INCREMENT NOT NULL, updated_at DATETIME NOT NULL, is_enabled TINYINT(1) NOT NULL, channel VARCHAR(255) NOT NULL, channel_id VARCHAR(15) NOT NULL, forecast_projects LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', updated_by VARCHAR(255) NOT NULL, time VARCHAR(5) NOT NULL, token VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE forecast_account_slack_team (id INT AUTO_INCREMENT NOT NULL, slack_team_id INT NOT NULL, updated_by_id INT NOT NULL, forecast_account_id INT NOT NULL, updated_at DATETIME NOT NULL, webhook_channel VARCHAR(255) NOT NULL, webhook_url VARCHAR(255) NOT NULL, webhook_configuration_url VARCHAR(255) NOT NULL, webhook_channel_id VARCHAR(15) NOT NULL, INDEX IDX_222599F168A87809 (slack_team_id), INDEX IDX_222599F1896DBBDE (updated_by_id), INDEX IDX_222599F13F38CF77 (forecast_account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE slack_team (id INT AUTO_INCREMENT NOT NULL, team_id VARCHAR(15) NOT NULL, team_name VARCHAR(255) NOT NULL, access_token VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE forecast_account_slack_team ADD CONSTRAINT FK_222599F168A87809 FOREIGN KEY (slack_team_id) REFERENCES slack_team (id)');
        $this->addSql('ALTER TABLE forecast_account_slack_team ADD CONSTRAINT FK_222599F1896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE forecast_account_slack_team ADD CONSTRAINT FK_222599F13F38CF77 FOREIGN KEY (forecast_account_id) REFERENCES forecast_account (id)');
        $this->addSql('DROP TABLE slack_channel');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE forecast_account_slack_team DROP FOREIGN KEY FK_222599F168A87809');
        $this->addSql('CREATE TABLE slack_channel (id INT AUTO_INCREMENT NOT NULL, updated_by_id INT NOT NULL, forecast_account_id INT NOT NULL, team_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, webhook_channel VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, webhook_url VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, access_token VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, updated_at DATETIME NOT NULL, team_id VARCHAR(15) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, webhook_configuration_url VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, webhook_channel_id VARCHAR(15) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_E33D2EE0896DBBDE (updated_by_id), INDEX IDX_E33D2EE03F38CF77 (forecast_account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE slack_channel ADD CONSTRAINT FK_E33D2EE03F38CF77 FOREIGN KEY (forecast_account_id) REFERENCES forecast_account (id)');
        $this->addSql('ALTER TABLE slack_channel ADD CONSTRAINT FK_E33D2EE0896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('DROP TABLE standup_meeting_reminder');
        $this->addSql('DROP TABLE forecast_account_slack_team');
        $this->addSql('DROP TABLE slack_team');
    }
}
