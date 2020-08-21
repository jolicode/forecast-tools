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
final class Version20200818012627 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE forecast_account_slack_team DROP FOREIGN KEY FK_222599F168A87809');
        $this->addSql('ALTER TABLE forecast_account_slack_team ADD CONSTRAINT FK_222599F168A87809 FOREIGN KEY (slack_team_id) REFERENCES slack_team (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE forecast_account_slack_team DROP FOREIGN KEY FK_222599F168A87809');
        $this->addSql('ALTER TABLE forecast_account_slack_team ADD CONSTRAINT FK_222599F168A87809 FOREIGN KEY (slack_team_id) REFERENCES slack_team (id)');
    }
}
