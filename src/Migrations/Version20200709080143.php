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
final class Version20200709080143 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE forecast_account ADD allow_non_admins TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE public_forecast CHANGE people people LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', CHANGE placeholders placeholders LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE forecast_account DROP allow_non_admins');
        $this->addSql('ALTER TABLE public_forecast CHANGE people people LONGTEXT CHARACTER SET utf8mb4 DEFAULT \'a:0:{}\' COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:array)\', CHANGE placeholders placeholders LONGTEXT CHARACTER SET utf8mb4 DEFAULT \'a:0:{}\' COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:array)\'');
    }
}
