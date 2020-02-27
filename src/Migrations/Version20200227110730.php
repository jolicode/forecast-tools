<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200227110730 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE invoice_due_delay_requirement (id INT AUTO_INCREMENT NOT NULL, updated_by_id INT DEFAULT NULL, harvest_account_id INT NOT NULL, harvest_client_id INT NOT NULL, updated_at DATETIME NOT NULL, delay INT NOT NULL, UNIQUE INDEX UNIQ_1A2EAA8BCE4ADD87 (harvest_client_id), INDEX IDX_1A2EAA8B896DBBDE (updated_by_id), INDEX IDX_1A2EAA8BA409F8D3 (harvest_account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE invoice_explanation (id INT AUTO_INCREMENT NOT NULL, created_by_id INT NOT NULL, invoicing_process_id INT NOT NULL, explanation_key VARCHAR(20) NOT NULL, explanation LONGTEXT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_9F6AD13DB03A8386 (created_by_id), INDEX IDX_9F6AD13D1ECC7DFB (invoicing_process_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE forecast_account (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, forecast_id INT NOT NULL, access_token VARCHAR(255) NOT NULL, refresh_token VARCHAR(255) NOT NULL, expires INT NOT NULL, created_at DATETIME NOT NULL, slug VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_9AFB041F989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE invoice_notes_requirement (id INT AUTO_INCREMENT NOT NULL, updated_by_id INT DEFAULT NULL, harvest_account_id INT NOT NULL, harvest_client_id INT NOT NULL, updated_at DATETIME NOT NULL, requirement LONGTEXT NOT NULL, INDEX IDX_320C104D896DBBDE (updated_by_id), INDEX IDX_320C104DA409F8D3 (harvest_account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE client_override (id INT AUTO_INCREMENT NOT NULL, forecast_reminder_id INT NOT NULL, created_by_id INT DEFAULT NULL, client_id INT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_ACE536203CF653E8 (forecast_reminder_id), INDEX IDX_ACE53620B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE slack_channel (id INT AUTO_INCREMENT NOT NULL, updated_by_id INT NOT NULL, forecast_account_id INT NOT NULL, team_name VARCHAR(255) NOT NULL, webhook_channel VARCHAR(255) NOT NULL, webhook_url VARCHAR(255) NOT NULL, access_token VARCHAR(255) NOT NULL, updated_at DATETIME NOT NULL, team_id VARCHAR(15) NOT NULL, webhook_configuration_url VARCHAR(255) NOT NULL, webhook_channel_id VARCHAR(15) NOT NULL, INDEX IDX_E33D2EE0896DBBDE (updated_by_id), INDEX IDX_E33D2EE03F38CF77 (forecast_account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project_override (id INT AUTO_INCREMENT NOT NULL, forecast_reminder_id INT NOT NULL, created_by_id INT DEFAULT NULL, project_id INT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_BF4D8D3E3CF653E8 (forecast_reminder_id), INDEX IDX_BF4D8D3EB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_forecast_account (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, forecast_account_id INT NOT NULL, is_admin TINYINT(1) NOT NULL, is_enabled TINYINT(1) NOT NULL, forecast_id INT NOT NULL, INDEX IDX_E7EE46C2A76ED395 (user_id), INDEX IDX_E7EE46C23F38CF77 (forecast_account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE harvest_account (id INT AUTO_INCREMENT NOT NULL, forecast_account_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, harvest_id INT NOT NULL, access_token VARCHAR(255) NOT NULL, refresh_token VARCHAR(255) NOT NULL, expires INT NOT NULL, base_uri VARCHAR(255) NOT NULL, do_not_check_timesheets_for LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', hide_skipped_users TINYINT(1) DEFAULT NULL, UNIQUE INDEX UNIQ_A787F78F3F38CF77 (forecast_account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE forecast_reminder (id INT AUTO_INCREMENT NOT NULL, updated_by_id INT NOT NULL, forecast_account_id INT NOT NULL, cron_expression VARCHAR(255) NOT NULL, updated_at DATETIME NOT NULL, default_activity_name VARCHAR(255) NOT NULL, time_off_activity_name VARCHAR(255) NOT NULL, time_off_projects LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', only_users LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', except_users LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', is_enabled TINYINT(1) NOT NULL, INDEX IDX_1C63E816896DBBDE (updated_by_id), UNIQUE INDEX UNIQ_1C63E8163F38CF77 (forecast_account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE invoicing_process (id INT AUTO_INCREMENT NOT NULL, created_by_id INT NOT NULL, forecast_account_id INT NOT NULL, harvest_account_id INT NOT NULL, created_at DATETIME NOT NULL, billing_period_start DATE NOT NULL, billing_period_end DATE NOT NULL, current_place VARCHAR(255) NOT NULL, INDEX IDX_A1F8541CB03A8386 (created_by_id), INDEX IDX_A1F8541C3F38CF77 (forecast_account_id), INDEX IDX_A1F8541CA409F8D3 (harvest_account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, forecast_id INT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, access_token VARCHAR(255) NOT NULL, refresh_token VARCHAR(255) NOT NULL, expires INT NOT NULL, is_enabled TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_8D93D649F8DCC97 (forecast_id), UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_harvest_account (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, harvest_account_id INT NOT NULL, harvest_id INT NOT NULL, is_admin TINYINT(1) NOT NULL, is_enabled TINYINT(1) NOT NULL, INDEX IDX_B55B9B00A76ED395 (user_id), INDEX IDX_B55B9B00A409F8D3 (harvest_account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE public_forecast (id INT AUTO_INCREMENT NOT NULL, forecast_account_id INT NOT NULL, created_by_id INT NOT NULL, token VARCHAR(255) NOT NULL, clients LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', projects LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', created_at DATETIME NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_FA874BB3F38CF77 (forecast_account_id), INDEX IDX_FA874BBB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE invoice_due_delay_requirement ADD CONSTRAINT FK_1A2EAA8B896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE invoice_due_delay_requirement ADD CONSTRAINT FK_1A2EAA8BA409F8D3 FOREIGN KEY (harvest_account_id) REFERENCES harvest_account (id)');
        $this->addSql('ALTER TABLE invoice_explanation ADD CONSTRAINT FK_9F6AD13DB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE invoice_explanation ADD CONSTRAINT FK_9F6AD13D1ECC7DFB FOREIGN KEY (invoicing_process_id) REFERENCES invoicing_process (id)');
        $this->addSql('ALTER TABLE invoice_notes_requirement ADD CONSTRAINT FK_320C104D896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE invoice_notes_requirement ADD CONSTRAINT FK_320C104DA409F8D3 FOREIGN KEY (harvest_account_id) REFERENCES harvest_account (id)');
        $this->addSql('ALTER TABLE client_override ADD CONSTRAINT FK_ACE536203CF653E8 FOREIGN KEY (forecast_reminder_id) REFERENCES forecast_reminder (id)');
        $this->addSql('ALTER TABLE client_override ADD CONSTRAINT FK_ACE53620B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE slack_channel ADD CONSTRAINT FK_E33D2EE0896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE slack_channel ADD CONSTRAINT FK_E33D2EE03F38CF77 FOREIGN KEY (forecast_account_id) REFERENCES forecast_account (id)');
        $this->addSql('ALTER TABLE project_override ADD CONSTRAINT FK_BF4D8D3E3CF653E8 FOREIGN KEY (forecast_reminder_id) REFERENCES forecast_reminder (id)');
        $this->addSql('ALTER TABLE project_override ADD CONSTRAINT FK_BF4D8D3EB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_forecast_account ADD CONSTRAINT FK_E7EE46C2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_forecast_account ADD CONSTRAINT FK_E7EE46C23F38CF77 FOREIGN KEY (forecast_account_id) REFERENCES forecast_account (id)');
        $this->addSql('ALTER TABLE harvest_account ADD CONSTRAINT FK_A787F78F3F38CF77 FOREIGN KEY (forecast_account_id) REFERENCES forecast_account (id)');
        $this->addSql('ALTER TABLE forecast_reminder ADD CONSTRAINT FK_1C63E816896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE forecast_reminder ADD CONSTRAINT FK_1C63E8163F38CF77 FOREIGN KEY (forecast_account_id) REFERENCES forecast_account (id)');
        $this->addSql('ALTER TABLE invoicing_process ADD CONSTRAINT FK_A1F8541CB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE invoicing_process ADD CONSTRAINT FK_A1F8541C3F38CF77 FOREIGN KEY (forecast_account_id) REFERENCES forecast_account (id)');
        $this->addSql('ALTER TABLE invoicing_process ADD CONSTRAINT FK_A1F8541CA409F8D3 FOREIGN KEY (harvest_account_id) REFERENCES harvest_account (id)');
        $this->addSql('ALTER TABLE user_harvest_account ADD CONSTRAINT FK_B55B9B00A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_harvest_account ADD CONSTRAINT FK_B55B9B00A409F8D3 FOREIGN KEY (harvest_account_id) REFERENCES harvest_account (id)');
        $this->addSql('ALTER TABLE public_forecast ADD CONSTRAINT FK_FA874BB3F38CF77 FOREIGN KEY (forecast_account_id) REFERENCES forecast_account (id)');
        $this->addSql('ALTER TABLE public_forecast ADD CONSTRAINT FK_FA874BBB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE slack_channel DROP FOREIGN KEY FK_E33D2EE03F38CF77');
        $this->addSql('ALTER TABLE user_forecast_account DROP FOREIGN KEY FK_E7EE46C23F38CF77');
        $this->addSql('ALTER TABLE harvest_account DROP FOREIGN KEY FK_A787F78F3F38CF77');
        $this->addSql('ALTER TABLE forecast_reminder DROP FOREIGN KEY FK_1C63E8163F38CF77');
        $this->addSql('ALTER TABLE invoicing_process DROP FOREIGN KEY FK_A1F8541C3F38CF77');
        $this->addSql('ALTER TABLE public_forecast DROP FOREIGN KEY FK_FA874BB3F38CF77');
        $this->addSql('ALTER TABLE invoice_due_delay_requirement DROP FOREIGN KEY FK_1A2EAA8BA409F8D3');
        $this->addSql('ALTER TABLE invoice_notes_requirement DROP FOREIGN KEY FK_320C104DA409F8D3');
        $this->addSql('ALTER TABLE invoicing_process DROP FOREIGN KEY FK_A1F8541CA409F8D3');
        $this->addSql('ALTER TABLE user_harvest_account DROP FOREIGN KEY FK_B55B9B00A409F8D3');
        $this->addSql('ALTER TABLE client_override DROP FOREIGN KEY FK_ACE536203CF653E8');
        $this->addSql('ALTER TABLE project_override DROP FOREIGN KEY FK_BF4D8D3E3CF653E8');
        $this->addSql('ALTER TABLE invoice_explanation DROP FOREIGN KEY FK_9F6AD13D1ECC7DFB');
        $this->addSql('ALTER TABLE invoice_due_delay_requirement DROP FOREIGN KEY FK_1A2EAA8B896DBBDE');
        $this->addSql('ALTER TABLE invoice_explanation DROP FOREIGN KEY FK_9F6AD13DB03A8386');
        $this->addSql('ALTER TABLE invoice_notes_requirement DROP FOREIGN KEY FK_320C104D896DBBDE');
        $this->addSql('ALTER TABLE client_override DROP FOREIGN KEY FK_ACE53620B03A8386');
        $this->addSql('ALTER TABLE slack_channel DROP FOREIGN KEY FK_E33D2EE0896DBBDE');
        $this->addSql('ALTER TABLE project_override DROP FOREIGN KEY FK_BF4D8D3EB03A8386');
        $this->addSql('ALTER TABLE user_forecast_account DROP FOREIGN KEY FK_E7EE46C2A76ED395');
        $this->addSql('ALTER TABLE forecast_reminder DROP FOREIGN KEY FK_1C63E816896DBBDE');
        $this->addSql('ALTER TABLE invoicing_process DROP FOREIGN KEY FK_A1F8541CB03A8386');
        $this->addSql('ALTER TABLE user_harvest_account DROP FOREIGN KEY FK_B55B9B00A76ED395');
        $this->addSql('ALTER TABLE public_forecast DROP FOREIGN KEY FK_FA874BBB03A8386');
        $this->addSql('DROP TABLE invoice_due_delay_requirement');
        $this->addSql('DROP TABLE invoice_explanation');
        $this->addSql('DROP TABLE forecast_account');
        $this->addSql('DROP TABLE invoice_notes_requirement');
        $this->addSql('DROP TABLE client_override');
        $this->addSql('DROP TABLE slack_channel');
        $this->addSql('DROP TABLE project_override');
        $this->addSql('DROP TABLE user_forecast_account');
        $this->addSql('DROP TABLE harvest_account');
        $this->addSql('DROP TABLE forecast_reminder');
        $this->addSql('DROP TABLE invoicing_process');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_harvest_account');
        $this->addSql('DROP TABLE public_forecast');
    }
}
