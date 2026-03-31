<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260331175517 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE catalog_language_vulnerabilities (id UUID NOT NULL, cve_id VARCHAR(50) DEFAULT NULL, osv_id VARCHAR(100) NOT NULL, summary TEXT NOT NULL, severity VARCHAR(255) NOT NULL, cvss_score DOUBLE PRECISION DEFAULT NULL, status VARCHAR(255) NOT NULL, patched_version VARCHAR(100) DEFAULT NULL, detected_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, resolved_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, language_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_9F1C1C2E82F1BAF4 ON catalog_language_vulnerabilities (language_id)');
        $this->addSql('CREATE TABLE coverage_snapshots (id UUID NOT NULL, project_id UUID NOT NULL, commit_hash VARCHAR(40) NOT NULL, coverage_percent DOUBLE PRECISION NOT NULL, source VARCHAR(255) NOT NULL, ref VARCHAR(255) NOT NULL, pipeline_id VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_coverage_project ON coverage_snapshots (project_id)');
        $this->addSql('CREATE INDEX idx_coverage_project_commit ON coverage_snapshots (project_id, commit_hash)');
        $this->addSql('ALTER TABLE catalog_language_vulnerabilities ADD CONSTRAINT FK_9F1C1C2E82F1BAF4 FOREIGN KEY (language_id) REFERENCES catalog_languages (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER INDEX idx_frameworks_language RENAME TO IDX_CAD2E37E82F1BAF4');
        $this->addSql('ALTER INDEX idx_frameworks_project RENAME TO IDX_CAD2E37E166D1F9C');
        $this->addSql('ALTER INDEX idx_languages_project RENAME TO IDX_5AECB9C4166D1F9C');
        $this->addSql('DROP INDEX idx_global_sync_status');
        $this->addSql('ALTER TABLE global_sync_jobs ALTER status TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE global_sync_jobs ALTER status DROP DEFAULT');
        $this->addSql('ALTER TABLE global_sync_jobs ALTER current_step DROP DEFAULT');
        $this->addSql('ALTER TABLE global_sync_jobs ALTER current_step_name TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE global_sync_jobs ALTER current_step_name DROP DEFAULT');
        $this->addSql('ALTER TABLE global_sync_jobs ALTER step_progress DROP DEFAULT');
        $this->addSql('ALTER TABLE global_sync_jobs ALTER step_total DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE catalog_language_vulnerabilities DROP CONSTRAINT FK_9F1C1C2E82F1BAF4');
        $this->addSql('DROP TABLE catalog_language_vulnerabilities');
        $this->addSql('DROP TABLE coverage_snapshots');
        $this->addSql('ALTER INDEX idx_cad2e37e166d1f9c RENAME TO idx_frameworks_project');
        $this->addSql('ALTER INDEX idx_cad2e37e82f1baf4 RENAME TO idx_frameworks_language');
        $this->addSql('ALTER INDEX idx_5aecb9c4166d1f9c RENAME TO idx_languages_project');
        $this->addSql('ALTER TABLE global_sync_jobs ALTER status TYPE VARCHAR(20)');
        $this->addSql('ALTER TABLE global_sync_jobs ALTER status SET DEFAULT \'running\'');
        $this->addSql('ALTER TABLE global_sync_jobs ALTER current_step SET DEFAULT 1');
        $this->addSql('ALTER TABLE global_sync_jobs ALTER current_step_name TYPE VARCHAR(50)');
        $this->addSql('ALTER TABLE global_sync_jobs ALTER current_step_name SET DEFAULT \'sync_projects\'');
        $this->addSql('ALTER TABLE global_sync_jobs ALTER step_progress SET DEFAULT 0');
        $this->addSql('ALTER TABLE global_sync_jobs ALTER step_total SET DEFAULT 0');
        $this->addSql('CREATE INDEX idx_global_sync_status ON global_sync_jobs (status)');
    }
}
