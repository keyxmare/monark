<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260311131828 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE catalog_providers (id UUID NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, url VARCHAR(500) NOT NULL, api_token TEXT NOT NULL, status VARCHAR(255) NOT NULL, last_sync_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE catalog_projects ADD external_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE catalog_projects ADD provider_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE catalog_projects ADD CONSTRAINT FK_6E446DBAA53A8AA FOREIGN KEY (provider_id) REFERENCES catalog_providers (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_6E446DBAA53A8AA ON catalog_projects (provider_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog_projects DROP CONSTRAINT FK_6E446DBAA53A8AA');
        $this->addSql('DROP INDEX IDX_6E446DBAA53A8AA');
        $this->addSql('ALTER TABLE catalog_projects DROP external_id');
        $this->addSql('ALTER TABLE catalog_projects DROP provider_id');
        $this->addSql('DROP TABLE catalog_providers');
    }
}
