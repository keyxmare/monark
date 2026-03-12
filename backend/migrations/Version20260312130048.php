<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260312130048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add last_synced_at to catalog_projects for incremental sync';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog_projects ADD last_synced_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog_projects DROP last_synced_at');
    }
}
