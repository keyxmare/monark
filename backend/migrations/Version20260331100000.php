<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260331100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create global_sync_jobs table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE global_sync_jobs (
            id UUID NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'running\',
            current_step INT NOT NULL DEFAULT 1,
            current_step_name VARCHAR(50) NOT NULL DEFAULT \'sync_projects\',
            step_progress INT NOT NULL DEFAULT 0,
            step_total INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_global_sync_status ON global_sync_jobs (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE global_sync_jobs');
    }
}
