<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200305180051 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE standup_meeting_reminder ADD slack_team_id INT NOT NULL');
        $this->addSql('ALTER TABLE standup_meeting_reminder ADD CONSTRAINT FK_E2EDDECC68A87809 FOREIGN KEY (slack_team_id) REFERENCES slack_team (id)');
        $this->addSql('CREATE INDEX IDX_E2EDDECC68A87809 ON standup_meeting_reminder (slack_team_id)');
        $this->addSql('ALTER TABLE forecast_reminder DROP is_enabled');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE forecast_reminder ADD is_enabled TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE standup_meeting_reminder DROP FOREIGN KEY FK_E2EDDECC68A87809');
        $this->addSql('DROP INDEX IDX_E2EDDECC68A87809 ON standup_meeting_reminder');
        $this->addSql('ALTER TABLE standup_meeting_reminder DROP slack_team_id');
    }
}
