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

final class Version20230627154654 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the cleanup filter for clients';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE harvest_account ADD do_not_cleanup_client_ids LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE harvest_account DROP do_not_cleanup_client_ids');
    }
}
