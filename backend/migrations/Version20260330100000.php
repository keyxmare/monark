<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260330100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace catalog_tech_stacks with catalog_languages and catalog_frameworks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS catalog_tech_stacks');

        $this->addSql('CREATE TABLE catalog_languages (
            id UUID NOT NULL,
            name VARCHAR(100) NOT NULL,
            version VARCHAR(50) NOT NULL,
            detected_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            eol_date DATE DEFAULT NULL,
            maintenance_status VARCHAR(20) DEFAULT NULL,
            project_id UUID NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_languages_project ON catalog_languages (project_id)');
        $this->addSql('ALTER TABLE catalog_languages ADD CONSTRAINT fk_languages_project
            FOREIGN KEY (project_id) REFERENCES catalog_projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE catalog_frameworks (
            id UUID NOT NULL,
            name VARCHAR(100) NOT NULL,
            version VARCHAR(50) NOT NULL,
            detected_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            latest_lts VARCHAR(50) DEFAULT NULL,
            lts_gap VARCHAR(100) DEFAULT NULL,
            maintenance_status VARCHAR(20) DEFAULT NULL,
            eol_date DATE DEFAULT NULL,
            version_synced_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            language_id UUID NOT NULL,
            project_id UUID NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_frameworks_project ON catalog_frameworks (project_id)');
        $this->addSql('CREATE INDEX idx_frameworks_language ON catalog_frameworks (language_id)');
        $this->addSql('ALTER TABLE catalog_frameworks ADD CONSTRAINT fk_frameworks_language
            FOREIGN KEY (language_id) REFERENCES catalog_languages (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE catalog_frameworks ADD CONSTRAINT fk_frameworks_project
            FOREIGN KEY (project_id) REFERENCES catalog_projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog_frameworks DROP CONSTRAINT fk_frameworks_language');
        $this->addSql('ALTER TABLE catalog_frameworks DROP CONSTRAINT fk_frameworks_project');
        $this->addSql('DROP TABLE catalog_frameworks');
        $this->addSql('ALTER TABLE catalog_languages DROP CONSTRAINT fk_languages_project');
        $this->addSql('DROP TABLE catalog_languages');

        $this->addSql('CREATE TABLE catalog_tech_stacks (
            id UUID NOT NULL,
            language VARCHAR(100) NOT NULL,
            framework VARCHAR(100) NOT NULL,
            version VARCHAR(50) NOT NULL,
            framework_version VARCHAR(50) NOT NULL,
            detected_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            project_id UUID NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            latest_lts VARCHAR(50) DEFAULT NULL,
            lts_gap VARCHAR(100) DEFAULT NULL,
            maintenance_status VARCHAR(20) DEFAULT NULL,
            eol_date DATE DEFAULT NULL,
            version_synced_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
    }
}
