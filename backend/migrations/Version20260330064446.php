<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260330064446 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE catalog_sync_jobs (id UUID NOT NULL, total_projects INT NOT NULL, completed_projects INT NOT NULL, status VARCHAR(255) NOT NULL, provider_id UUID DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('DROP TABLE sync_jobs');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sync_jobs (id UUID NOT NULL, type VARCHAR(255) NOT NULL, total_items INT NOT NULL, completed_items INT NOT NULL, status VARCHAR(255) NOT NULL, metadata JSON NOT NULL, provider_id UUID DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_sync_jobs_status ON sync_jobs (status)');
        $this->addSql('DROP TABLE catalog_sync_jobs');
    }
}
