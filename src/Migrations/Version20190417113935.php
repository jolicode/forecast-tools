<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190417113935 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE project_override (id INT AUTO_INCREMENT NOT NULL, alert_id INT NOT NULL, created_by_id INT DEFAULT NULL, project_id INT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_BF4D8D3E93035F72 (alert_id), INDEX IDX_BF4D8D3EB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE forecast_account (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, forecast_id INT NOT NULL, access_token VARCHAR(255) NOT NULL, refresh_token VARCHAR(255) NOT NULL, expires INT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE forecast_account_user (forecast_account_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_8F5BCFF73F38CF77 (forecast_account_id), INDEX IDX_8F5BCFF7A76ED395 (user_id), PRIMARY KEY(forecast_account_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, forecast_id INT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, access_token VARCHAR(255) NOT NULL, refresh_token VARCHAR(255) NOT NULL, expires INT NOT NULL, is_enabled TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_8D93D649F8DCC97 (forecast_id), UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE forecast_alert (id INT AUTO_INCREMENT NOT NULL, forecast_account_id INT NOT NULL, created_by_id INT NOT NULL, name VARCHAR(255) NOT NULL, cron_expression VARCHAR(255) NOT NULL, slack_web_hook VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, default_activity_name VARCHAR(255) NOT NULL, time_off_activity_name VARCHAR(255) NOT NULL, time_off_projects LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', only_users LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', INDEX IDX_2E2D4033F38CF77 (forecast_account_id), INDEX IDX_2E2D403B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE public_forecast (id INT AUTO_INCREMENT NOT NULL, forecast_account_id INT NOT NULL, created_by_id INT NOT NULL, token VARCHAR(255) NOT NULL, clients LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', projects LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', created_at DATETIME NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_FA874BB3F38CF77 (forecast_account_id), INDEX IDX_FA874BBB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE client_override (id INT AUTO_INCREMENT NOT NULL, alert_id INT NOT NULL, created_by_id INT DEFAULT NULL, client_id INT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_ACE5362093035F72 (alert_id), INDEX IDX_ACE53620B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project_override ADD CONSTRAINT FK_BF4D8D3E93035F72 FOREIGN KEY (alert_id) REFERENCES forecast_alert (id)');
        $this->addSql('ALTER TABLE project_override ADD CONSTRAINT FK_BF4D8D3EB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE forecast_account_user ADD CONSTRAINT FK_8F5BCFF73F38CF77 FOREIGN KEY (forecast_account_id) REFERENCES forecast_account (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE forecast_account_user ADD CONSTRAINT FK_8F5BCFF7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE forecast_alert ADD CONSTRAINT FK_2E2D4033F38CF77 FOREIGN KEY (forecast_account_id) REFERENCES forecast_account (id)');
        $this->addSql('ALTER TABLE forecast_alert ADD CONSTRAINT FK_2E2D403B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE public_forecast ADD CONSTRAINT FK_FA874BB3F38CF77 FOREIGN KEY (forecast_account_id) REFERENCES forecast_account (id)');
        $this->addSql('ALTER TABLE public_forecast ADD CONSTRAINT FK_FA874BBB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE client_override ADD CONSTRAINT FK_ACE5362093035F72 FOREIGN KEY (alert_id) REFERENCES forecast_alert (id)');
        $this->addSql('ALTER TABLE client_override ADD CONSTRAINT FK_ACE53620B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE forecast_account_user DROP FOREIGN KEY FK_8F5BCFF73F38CF77');
        $this->addSql('ALTER TABLE forecast_alert DROP FOREIGN KEY FK_2E2D4033F38CF77');
        $this->addSql('ALTER TABLE public_forecast DROP FOREIGN KEY FK_FA874BB3F38CF77');
        $this->addSql('ALTER TABLE project_override DROP FOREIGN KEY FK_BF4D8D3EB03A8386');
        $this->addSql('ALTER TABLE forecast_account_user DROP FOREIGN KEY FK_8F5BCFF7A76ED395');
        $this->addSql('ALTER TABLE forecast_alert DROP FOREIGN KEY FK_2E2D403B03A8386');
        $this->addSql('ALTER TABLE public_forecast DROP FOREIGN KEY FK_FA874BBB03A8386');
        $this->addSql('ALTER TABLE client_override DROP FOREIGN KEY FK_ACE53620B03A8386');
        $this->addSql('ALTER TABLE project_override DROP FOREIGN KEY FK_BF4D8D3E93035F72');
        $this->addSql('ALTER TABLE client_override DROP FOREIGN KEY FK_ACE5362093035F72');
        $this->addSql('DROP TABLE project_override');
        $this->addSql('DROP TABLE forecast_account');
        $this->addSql('DROP TABLE forecast_account_user');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE forecast_alert');
        $this->addSql('DROP TABLE public_forecast');
        $this->addSql('DROP TABLE client_override');
    }
}
