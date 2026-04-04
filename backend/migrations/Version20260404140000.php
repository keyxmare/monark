<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260404140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional project_id to global_sync_jobs for single-project sync';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE global_sync_jobs ADD COLUMN project_id UUID DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE global_sync_jobs DROP COLUMN project_id');
    }
}
