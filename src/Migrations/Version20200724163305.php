<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200724163305 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_override DROP FOREIGN KEY FK_ACE536203CF653E8');
        $this->addSql('ALTER TABLE client_override DROP FOREIGN KEY FK_ACE53620B03A8386');
        $this->addSql('ALTER TABLE client_override ADD CONSTRAINT FK_ACE536203CF653E8 FOREIGN KEY (forecast_reminder_id) REFERENCES forecast_reminder (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE client_override ADD CONSTRAINT FK_ACE53620B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE forecast_account_slack_team DROP FOREIGN KEY FK_222599F13F38CF77');
        $this->addSql('ALTER TABLE forecast_account_slack_team DROP FOREIGN KEY FK_222599F1896DBBDE');
        $this->addSql('ALTER TABLE forecast_account_slack_team CHANGE updated_by_id updated_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE forecast_account_slack_team ADD CONSTRAINT FK_222599F13F38CF77 FOREIGN KEY (forecast_account_id) REFERENCES forecast_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE forecast_account_slack_team ADD CONSTRAINT FK_222599F1896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE forecast_reminder DROP FOREIGN KEY FK_1C63E8163F38CF77');
        $this->addSql('ALTER TABLE forecast_reminder DROP FOREIGN KEY FK_1C63E816896DBBDE');
        $this->addSql('ALTER TABLE forecast_reminder CHANGE updated_by_id updated_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE forecast_reminder ADD CONSTRAINT FK_1C63E8163F38CF77 FOREIGN KEY (forecast_account_id) REFERENCES forecast_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE forecast_reminder ADD CONSTRAINT FK_1C63E816896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE invoice_due_delay_requirement DROP FOREIGN KEY FK_1A2EAA8BA409F8D3');
        $this->addSql('ALTER TABLE invoice_due_delay_requirement ADD CONSTRAINT FK_1A2EAA8BA409F8D3 FOREIGN KEY (harvest_account_id) REFERENCES harvest_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invoice_explanation DROP FOREIGN KEY FK_9F6AD13D1ECC7DFB');
        $this->addSql('ALTER TABLE invoice_explanation DROP FOREIGN KEY FK_9F6AD13DB03A8386');
        $this->addSql('ALTER TABLE invoice_explanation CHANGE created_by_id created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE invoice_explanation ADD CONSTRAINT FK_9F6AD13D1ECC7DFB FOREIGN KEY (invoicing_process_id) REFERENCES invoicing_process (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invoice_explanation ADD CONSTRAINT FK_9F6AD13DB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE invoice_notes_requirement DROP FOREIGN KEY FK_320C104DA409F8D3');
        $this->addSql('ALTER TABLE invoice_notes_requirement ADD CONSTRAINT FK_320C104DA409F8D3 FOREIGN KEY (harvest_account_id) REFERENCES harvest_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invoicing_process DROP FOREIGN KEY FK_A1F8541C3F38CF77');
        $this->addSql('ALTER TABLE invoicing_process DROP FOREIGN KEY FK_A1F8541CA409F8D3');
        $this->addSql('ALTER TABLE invoicing_process DROP FOREIGN KEY FK_A1F8541CB03A8386');
        $this->addSql('ALTER TABLE invoicing_process CHANGE created_by_id created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE invoicing_process ADD CONSTRAINT FK_A1F8541C3F38CF77 FOREIGN KEY (forecast_account_id) REFERENCES forecast_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invoicing_process ADD CONSTRAINT FK_A1F8541CA409F8D3 FOREIGN KEY (harvest_account_id) REFERENCES harvest_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE invoicing_process ADD CONSTRAINT FK_A1F8541CB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE project_override DROP FOREIGN KEY FK_BF4D8D3E3CF653E8');
        $this->addSql('ALTER TABLE project_override DROP FOREIGN KEY FK_BF4D8D3EB03A8386');
        $this->addSql('ALTER TABLE project_override ADD CONSTRAINT FK_BF4D8D3E3CF653E8 FOREIGN KEY (forecast_reminder_id) REFERENCES forecast_reminder (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE project_override ADD CONSTRAINT FK_BF4D8D3EB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE public_forecast DROP FOREIGN KEY FK_FA874BB3F38CF77');
        $this->addSql('ALTER TABLE public_forecast DROP FOREIGN KEY FK_FA874BBB03A8386');
        $this->addSql('ALTER TABLE public_forecast CHANGE created_by_id created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE public_forecast ADD CONSTRAINT FK_FA874BB3F38CF77 FOREIGN KEY (forecast_account_id) REFERENCES forecast_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE public_forecast ADD CONSTRAINT FK_FA874BBB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE standup_meeting_reminder DROP FOREIGN KEY FK_E2EDDECC68A87809');
        $this->addSql('ALTER TABLE standup_meeting_reminder ADD CONSTRAINT FK_E2EDDECC68A87809 FOREIGN KEY (slack_team_id) REFERENCES slack_team (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user ADD default_forecast_account_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64996E4242A FOREIGN KEY (default_forecast_account_id) REFERENCES forecast_account (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_8D93D64996E4242A ON user (default_forecast_account_id)');
        $this->addSql('ALTER TABLE user_forecast_account DROP FOREIGN KEY FK_E7EE46C23F38CF77');
        $this->addSql('ALTER TABLE user_forecast_account DROP FOREIGN KEY FK_E7EE46C2A76ED395');
        $this->addSql('ALTER TABLE user_forecast_account ADD CONSTRAINT FK_E7EE46C23F38CF77 FOREIGN KEY (forecast_account_id) REFERENCES forecast_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_forecast_account ADD CONSTRAINT FK_E7EE46C2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_harvest_account DROP FOREIGN KEY FK_B55B9B00A409F8D3');
        $this->addSql('ALTER TABLE user_harvest_account DROP FOREIGN KEY FK_B55B9B00A76ED395');
        $this->addSql('ALTER TABLE user_harvest_account ADD CONSTRAINT FK_B55B9B00A409F8D3 FOREIGN KEY (harvest_account_id) REFERENCES harvest_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_harvest_account ADD CONSTRAINT FK_B55B9B00A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client_override DROP FOREIGN KEY FK_ACE536203CF653E8');
        $this->addSql('ALTER TABLE client_override DROP FOREIGN KEY FK_ACE53620B03A8386');
        $this->addSql('ALTER TABLE client_override ADD CONSTRAINT FK_ACE536203CF653E8 FOREIGN KEY (forecast_reminder_id) REFERENCES forecast_reminder (id)');
        $this->addSql('ALTER TABLE client_override ADD CONSTRAINT FK_ACE53620B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE forecast_account_slack_team DROP FOREIGN KEY FK_222599F1896DBBDE');
        $this->addSql('ALTER TABLE forecast_account_slack_team DROP FOREIGN KEY FK_222599F13F38CF77');
        $this->addSql('ALTER TABLE forecast_account_slack_team CHANGE updated_by_id updated_by_id INT NOT NULL');
        $this->addSql('ALTER TABLE forecast_account_slack_team ADD CONSTRAINT FK_222599F1896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE forecast_account_slack_team ADD CONSTRAINT FK_222599F13F38CF77 FOREIGN KEY (forecast_account_id) REFERENCES forecast_account (id)');
        $this->addSql('ALTER TABLE forecast_reminder DROP FOREIGN KEY FK_1C63E816896DBBDE');
        $this->addSql('ALTER TABLE forecast_reminder DROP FOREIGN KEY FK_1C63E8163F38CF77');
        $this->addSql('ALTER TABLE forecast_reminder CHANGE updated_by_id updated_by_id INT NOT NULL');
        $this->addSql('ALTER TABLE forecast_reminder ADD CONSTRAINT FK_1C63E816896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE forecast_reminder ADD CONSTRAINT FK_1C63E8163F38CF77 FOREIGN KEY (forecast_account_id) REFERENCES forecast_account (id)');
        $this->addSql('ALTER TABLE invoice_due_delay_requirement DROP FOREIGN KEY FK_1A2EAA8BA409F8D3');
        $this->addSql('ALTER TABLE invoice_due_delay_requirement ADD CONSTRAINT FK_1A2EAA8BA409F8D3 FOREIGN KEY (harvest_account_id) REFERENCES harvest_account (id)');
        $this->addSql('ALTER TABLE invoice_explanation DROP FOREIGN KEY FK_9F6AD13DB03A8386');
        $this->addSql('ALTER TABLE invoice_explanation DROP FOREIGN KEY FK_9F6AD13D1ECC7DFB');
        $this->addSql('ALTER TABLE invoice_explanation CHANGE created_by_id created_by_id INT NOT NULL');
        $this->addSql('ALTER TABLE invoice_explanation ADD CONSTRAINT FK_9F6AD13DB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE invoice_explanation ADD CONSTRAINT FK_9F6AD13D1ECC7DFB FOREIGN KEY (invoicing_process_id) REFERENCES invoicing_process (id)');
        $this->addSql('ALTER TABLE invoice_notes_requirement DROP FOREIGN KEY FK_320C104DA409F8D3');
        $this->addSql('ALTER TABLE invoice_notes_requirement ADD CONSTRAINT FK_320C104DA409F8D3 FOREIGN KEY (harvest_account_id) REFERENCES harvest_account (id)');
        $this->addSql('ALTER TABLE invoicing_process DROP FOREIGN KEY FK_A1F8541CB03A8386');
        $this->addSql('ALTER TABLE invoicing_process DROP FOREIGN KEY FK_A1F8541C3F38CF77');
        $this->addSql('ALTER TABLE invoicing_process DROP FOREIGN KEY FK_A1F8541CA409F8D3');
        $this->addSql('ALTER TABLE invoicing_process CHANGE created_by_id created_by_id INT NOT NULL');
        $this->addSql('ALTER TABLE invoicing_process ADD CONSTRAINT FK_A1F8541CB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE invoicing_process ADD CONSTRAINT FK_A1F8541C3F38CF77 FOREIGN KEY (forecast_account_id) REFERENCES forecast_account (id)');
        $this->addSql('ALTER TABLE invoicing_process ADD CONSTRAINT FK_A1F8541CA409F8D3 FOREIGN KEY (harvest_account_id) REFERENCES harvest_account (id)');
        $this->addSql('ALTER TABLE project_override DROP FOREIGN KEY FK_BF4D8D3E3CF653E8');
        $this->addSql('ALTER TABLE project_override DROP FOREIGN KEY FK_BF4D8D3EB03A8386');
        $this->addSql('ALTER TABLE project_override ADD CONSTRAINT FK_BF4D8D3E3CF653E8 FOREIGN KEY (forecast_reminder_id) REFERENCES forecast_reminder (id)');
        $this->addSql('ALTER TABLE project_override ADD CONSTRAINT FK_BF4D8D3EB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE public_forecast DROP FOREIGN KEY FK_FA874BB3F38CF77');
        $this->addSql('ALTER TABLE public_forecast DROP FOREIGN KEY FK_FA874BBB03A8386');
        $this->addSql('ALTER TABLE public_forecast CHANGE created_by_id created_by_id INT NOT NULL');
        $this->addSql('ALTER TABLE public_forecast ADD CONSTRAINT FK_FA874BB3F38CF77 FOREIGN KEY (forecast_account_id) REFERENCES forecast_account (id)');
        $this->addSql('ALTER TABLE public_forecast ADD CONSTRAINT FK_FA874BBB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE standup_meeting_reminder DROP FOREIGN KEY FK_E2EDDECC68A87809');
        $this->addSql('ALTER TABLE standup_meeting_reminder ADD CONSTRAINT FK_E2EDDECC68A87809 FOREIGN KEY (slack_team_id) REFERENCES slack_team (id)');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64996E4242A');
        $this->addSql('DROP INDEX IDX_8D93D64996E4242A ON user');
        $this->addSql('ALTER TABLE user DROP default_forecast_account_id');
        $this->addSql('ALTER TABLE user_forecast_account DROP FOREIGN KEY FK_E7EE46C2A76ED395');
        $this->addSql('ALTER TABLE user_forecast_account DROP FOREIGN KEY FK_E7EE46C23F38CF77');
        $this->addSql('ALTER TABLE user_forecast_account ADD CONSTRAINT FK_E7EE46C2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_forecast_account ADD CONSTRAINT FK_E7EE46C23F38CF77 FOREIGN KEY (forecast_account_id) REFERENCES forecast_account (id)');
        $this->addSql('ALTER TABLE user_harvest_account DROP FOREIGN KEY FK_B55B9B00A76ED395');
        $this->addSql('ALTER TABLE user_harvest_account DROP FOREIGN KEY FK_B55B9B00A409F8D3');
        $this->addSql('ALTER TABLE user_harvest_account ADD CONSTRAINT FK_B55B9B00A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_harvest_account ADD CONSTRAINT FK_B55B9B00A409F8D3 FOREIGN KEY (harvest_account_id) REFERENCES harvest_account (id)');
    }
}
