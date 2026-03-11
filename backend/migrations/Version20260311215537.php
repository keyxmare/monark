<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260311215537 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sync_tasks (id UUID NOT NULL, type VARCHAR(255) NOT NULL, severity VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, description TEXT NOT NULL, status VARCHAR(255) NOT NULL, metadata JSON NOT NULL, project_id UUID NOT NULL, resolved_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_sync_task_project_type_status ON sync_tasks (project_id, type, status)');
        $this->addSql('ALTER TABLE catalog_providers ADD username VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE catalog_providers ALTER api_token DROP NOT NULL');
        $this->addSql('ALTER INDEX idx_dependencies_project_id RENAME TO IDX_EA0F708D166D1F9C');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE sync_tasks');
        $this->addSql('ALTER TABLE catalog_providers DROP username');
        $this->addSql('ALTER TABLE catalog_providers ALTER api_token SET NOT NULL');
        $this->addSql('ALTER INDEX idx_ea0f708d166d1f9c RENAME TO idx_dependencies_project_id');
    }
}
