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

final class Version20230627094146 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'initial migration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE invoicing_process (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, forecast_account_id INT NOT NULL, harvest_account_id INT NOT NULL, created_at DATETIME NOT NULL, billing_period_start DATE NOT NULL, billing_period_end DATE NOT NULL, current_place VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_A1F8541C3F38CF77 (forecast_account_id), INDEX IDX_A1F8541CA409F8D3 (harvest_account_id), INDEX IDX_A1F8541CB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE invoice_explanation (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, invoicing_process_id INT NOT NULL, explanation_key VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, explanation LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, INDEX IDX_9F6AD13DB03A8386 (created_by_id), INDEX IDX_9F6AD13D1ECC7DFB (invoicing_process_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE invoice_notes_requirement (id INT AUTO_INCREMENT NOT NULL, updated_by_id INT DEFAULT NULL, harvest_account_id INT NOT NULL, harvest_client_id INT NOT NULL, updated_at DATETIME NOT NULL, requirement LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_320C104D896DBBDE (updated_by_id), INDEX IDX_320C104DA409F8D3 (harvest_account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE invoice_due_delay_requirement (id INT AUTO_INCREMENT NOT NULL, updated_by_id INT DEFAULT NULL, harvest_account_id INT NOT NULL, harvest_client_id INT NOT NULL, updated_at DATETIME NOT NULL, delay INT NOT NULL, UNIQUE INDEX UNIQ_1A2EAA8BCE4ADD87 (harvest_client_id), INDEX IDX_1A2EAA8B896DBBDE (updated_by_id), INDEX IDX_1A2EAA8BA409F8D3 (harvest_account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE project_override (id INT AUTO_INCREMENT NOT NULL, forecast_reminder_id INT NOT NULL, created_by_id INT DEFAULT NULL, project_id INT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, INDEX IDX_BF4D8D3E3CF653E8 (forecast_reminder_id), INDEX IDX_BF4D8D3EB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE slack_request (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, url VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, response LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, request_payload LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, xslack_signature VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, xslack_request_timestamp VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, is_signature_valid TINYINT(1) NOT NULL, request_content LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, default_forecast_account_id INT DEFAULT NULL, forecast_id INT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, email VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, access_token VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, refresh_token VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, expires INT NOT NULL, is_enabled TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, is_super_admin TINYINT(1) NOT NULL, INDEX IDX_8D93D64996E4242A (default_forecast_account_id), UNIQUE INDEX UNIQ_8D93D649F8DCC97 (forecast_id), UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE client_override (id INT AUTO_INCREMENT NOT NULL, forecast_reminder_id INT NOT NULL, created_by_id INT DEFAULT NULL, client_id INT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, INDEX IDX_ACE53620B03A8386 (created_by_id), INDEX IDX_ACE536203CF653E8 (forecast_reminder_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE forecast_reminder (id INT AUTO_INCREMENT NOT NULL, updated_by_id INT DEFAULT NULL, forecast_account_id INT NOT NULL, cron_expression VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, updated_at DATETIME NOT NULL, last_time_sent_at DATETIME DEFAULT NULL, default_activity_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, time_off_activity_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, time_off_projects LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:array)\', only_users LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:array)\', except_users LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:array)\', INDEX IDX_1C63E816896DBBDE (updated_by_id), UNIQUE INDEX UNIQ_1C63E8163F38CF77 (forecast_account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE user_forecast_account (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, forecast_account_id INT NOT NULL, is_admin TINYINT(1) NOT NULL, is_enabled TINYINT(1) NOT NULL, forecast_id INT NOT NULL, INDEX IDX_E7EE46C23F38CF77 (forecast_account_id), INDEX IDX_E7EE46C2A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE harvest_account (id INT AUTO_INCREMENT NOT NULL, forecast_account_id INT DEFAULT NULL, timesheet_reminder_slack_team_id INT DEFAULT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, harvest_id INT NOT NULL, access_token VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, refresh_token VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, expires INT NOT NULL, base_uri VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, do_not_check_timesheets_for LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:array)\', hide_skipped_users TINYINT(1) DEFAULT NULL, do_not_send_timesheet_reminder_for LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:array)\', UNIQUE INDEX UNIQ_A787F78F3F38CF77 (forecast_account_id), INDEX IDX_A787F78F207F0753 (timesheet_reminder_slack_team_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE forecast_account_slack_team (id INT AUTO_INCREMENT NOT NULL, slack_team_id INT NOT NULL, updated_by_id INT DEFAULT NULL, forecast_account_id INT NOT NULL, updated_at DATETIME NOT NULL, channel VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, channel_id VARCHAR(15) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, errors_count INT NOT NULL, INDEX IDX_222599F13F38CF77 (forecast_account_id), INDEX IDX_222599F168A87809 (slack_team_id), INDEX IDX_222599F1896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE user_harvest_account (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, harvest_account_id INT NOT NULL, harvest_id INT NOT NULL, is_admin TINYINT(1) NOT NULL, is_enabled TINYINT(1) NOT NULL, INDEX IDX_B55B9B00A76ED395 (user_id), INDEX IDX_B55B9B00A409F8D3 (harvest_account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE standup_meeting_reminder (id INT AUTO_INCREMENT NOT NULL, slack_team_id INT NOT NULL, updated_at DATETIME NOT NULL, is_enabled TINYINT(1) NOT NULL, channel_id VARCHAR(15) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, forecast_projects LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:array)\', updated_by VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, time VARCHAR(5) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_E2EDDECC68A87809 (slack_team_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE forecast_account (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, forecast_id INT NOT NULL, access_token VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, refresh_token VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, expires INT NOT NULL, created_at DATETIME NOT NULL, slug VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, allow_non_admins TINYINT(1) DEFAULT NULL, UNIQUE INDEX UNIQ_9AFB041F989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE public_forecast (id INT AUTO_INCREMENT NOT NULL, forecast_account_id INT NOT NULL, created_by_id INT DEFAULT NULL, token VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, clients LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:array)\', projects LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:array)\', created_at DATETIME NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, people LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:array)\', placeholders LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:array)\', INDEX IDX_FA874BB3F38CF77 (forecast_account_id), INDEX IDX_FA874BBB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE slack_call (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, url VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, request_body LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, response_body LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, status_code INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE slack_team (id INT AUTO_INCREMENT NOT NULL, team_id VARCHAR(15) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, team_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, access_token VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE invoicing_process');
        $this->addSql('DROP TABLE invoice_explanation');
        $this->addSql('DROP TABLE invoice_notes_requirement');
        $this->addSql('DROP TABLE invoice_due_delay_requirement');
        $this->addSql('DROP TABLE project_override');
        $this->addSql('DROP TABLE slack_request');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE client_override');
        $this->addSql('DROP TABLE forecast_reminder');
        $this->addSql('DROP TABLE user_forecast_account');
        $this->addSql('DROP TABLE harvest_account');
        $this->addSql('DROP TABLE forecast_account_slack_team');
        $this->addSql('DROP TABLE user_harvest_account');
        $this->addSql('DROP TABLE standup_meeting_reminder');
        $this->addSql('DROP TABLE forecast_account');
        $this->addSql('DROP TABLE public_forecast');
        $this->addSql('DROP TABLE slack_call');
        $this->addSql('DROP TABLE slack_team');
    }
}
