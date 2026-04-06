<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260406081814 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activity_build_metrics (id UUID NOT NULL, project_id UUID NOT NULL, commit_sha VARCHAR(40) NOT NULL, ref VARCHAR(255) NOT NULL, backend_coverage DOUBLE PRECISION DEFAULT NULL, frontend_coverage DOUBLE PRECISION DEFAULT NULL, mutation_score DOUBLE PRECISION DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_build_metric_project_date ON activity_build_metrics (project_id, created_at)');
        $this->addSql('CREATE TABLE catalog_frameworks (id UUID NOT NULL, name VARCHAR(100) NOT NULL, version VARCHAR(50) NOT NULL, detected_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, latest_lts VARCHAR(50) DEFAULT NULL, lts_gap VARCHAR(100) DEFAULT NULL, maintenance_status VARCHAR(20) DEFAULT NULL, eol_date DATE DEFAULT NULL, version_synced_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, language_name VARCHAR(100) NOT NULL, language_version VARCHAR(50) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, project_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_CAD2E37E166D1F9C ON catalog_frameworks (project_id)');
        $this->addSql('CREATE TABLE catalog_language_vulnerabilities (id UUID NOT NULL, language_name VARCHAR(100) NOT NULL, language_version VARCHAR(50) NOT NULL, project_id UUID NOT NULL, cve_id VARCHAR(50) DEFAULT NULL, osv_id VARCHAR(100) NOT NULL, summary TEXT NOT NULL, severity VARCHAR(255) NOT NULL, cvss_score DOUBLE PRECISION DEFAULT NULL, status VARCHAR(255) NOT NULL, patched_version VARCHAR(100) DEFAULT NULL, detected_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, resolved_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE catalog_projects (id UUID NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, repository_url VARCHAR(500) NOT NULL, default_branch VARCHAR(100) NOT NULL, visibility VARCHAR(255) NOT NULL, owner_id UUID NOT NULL, external_id VARCHAR(255) DEFAULT NULL, last_synced_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, provider_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_6E446DBAA53A8AA ON catalog_projects (provider_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_project_slug ON catalog_projects (slug)');
        $this->addSql('CREATE TABLE catalog_providers (id UUID NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, url VARCHAR(500) NOT NULL, api_token TEXT DEFAULT NULL, username VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, last_sync_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE catalog_sync_jobs (id UUID NOT NULL, total_projects INT NOT NULL, completed_projects INT NOT NULL, status VARCHAR(255) NOT NULL, provider_id UUID DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE coverage_snapshots (id UUID NOT NULL, project_id UUID NOT NULL, commit_hash VARCHAR(40) NOT NULL, coverage_percent DOUBLE PRECISION NOT NULL, source VARCHAR(255) NOT NULL, ref VARCHAR(255) NOT NULL, pipeline_id VARCHAR(255) DEFAULT NULL, jobs JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_coverage_project ON coverage_snapshots (project_id)');
        $this->addSql('CREATE INDEX idx_coverage_project_commit ON coverage_snapshots (project_id, commit_hash)');
        $this->addSql('CREATE TABLE dependencies (id UUID NOT NULL, name VARCHAR(255) NOT NULL, current_version VARCHAR(100) NOT NULL, latest_version VARCHAR(100) NOT NULL, lts_version VARCHAR(100) NOT NULL, package_manager VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, is_outdated BOOLEAN NOT NULL, registry_status VARCHAR(255) NOT NULL, repository_url VARCHAR(2048) DEFAULT NULL, project_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE dependency_versions (id UUID NOT NULL, dependency_name VARCHAR(255) NOT NULL, package_manager VARCHAR(255) NOT NULL, version VARCHAR(100) NOT NULL, release_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_lts BOOLEAN NOT NULL, is_latest BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_dep_version_lookup ON dependency_versions (dependency_name, package_manager)');
        $this->addSql('CREATE UNIQUE INDEX uniq_dep_version ON dependency_versions (dependency_name, package_manager, version)');
        $this->addSql('CREATE TABLE global_sync_jobs (id UUID NOT NULL, status VARCHAR(255) NOT NULL, current_step INT NOT NULL, current_step_name VARCHAR(255) NOT NULL, step_progress INT NOT NULL, step_total INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, project_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE identity_users (id UUID NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, avatar VARCHAR(255) DEFAULT NULL, roles JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_email ON identity_users (email)');
        $this->addSql('CREATE TABLE product_versions (id UUID NOT NULL, product_name VARCHAR(255) NOT NULL, package_manager VARCHAR(255) DEFAULT NULL, version VARCHAR(100) NOT NULL, release_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_lts BOOLEAN NOT NULL, is_latest BOOLEAN NOT NULL, eol_date VARCHAR(20) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_product_version_lookup ON product_versions (product_name, package_manager)');
        $this->addSql('CREATE INDEX idx_product_version_latest ON product_versions (product_name, package_manager, is_latest)');
        $this->addSql('CREATE UNIQUE INDEX uniq_product_version ON product_versions (product_name, package_manager, version)');
        $this->addSql('CREATE TABLE products (id UUID NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, package_manager VARCHAR(255) DEFAULT NULL, resolver_source VARCHAR(255) NOT NULL, latest_version VARCHAR(100) DEFAULT NULL, lts_version VARCHAR(100) DEFAULT NULL, last_synced_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_product ON products (name, package_manager)');
        $this->addSql('CREATE TABLE vulnerabilities (id UUID NOT NULL, cve_id VARCHAR(50) NOT NULL, severity VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, description TEXT NOT NULL, patched_version VARCHAR(100) NOT NULL, status VARCHAR(255) NOT NULL, detected_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, dependency_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_459DFAF7C2F67723 ON vulnerabilities (dependency_id)');
        $this->addSql('ALTER TABLE catalog_frameworks ADD CONSTRAINT FK_CAD2E37E166D1F9C FOREIGN KEY (project_id) REFERENCES catalog_projects (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE catalog_projects ADD CONSTRAINT FK_6E446DBAA53A8AA FOREIGN KEY (provider_id) REFERENCES catalog_providers (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE vulnerabilities ADD CONSTRAINT FK_459DFAF7C2F67723 FOREIGN KEY (dependency_id) REFERENCES dependencies (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE catalog_frameworks DROP CONSTRAINT FK_CAD2E37E166D1F9C');
        $this->addSql('ALTER TABLE catalog_projects DROP CONSTRAINT FK_6E446DBAA53A8AA');
        $this->addSql('ALTER TABLE vulnerabilities DROP CONSTRAINT FK_459DFAF7C2F67723');
        $this->addSql('DROP TABLE activity_build_metrics');
        $this->addSql('DROP TABLE catalog_frameworks');
        $this->addSql('DROP TABLE catalog_language_vulnerabilities');
        $this->addSql('DROP TABLE catalog_projects');
        $this->addSql('DROP TABLE catalog_providers');
        $this->addSql('DROP TABLE catalog_sync_jobs');
        $this->addSql('DROP TABLE coverage_snapshots');
        $this->addSql('DROP TABLE dependencies');
        $this->addSql('DROP TABLE dependency_versions');
        $this->addSql('DROP TABLE global_sync_jobs');
        $this->addSql('DROP TABLE identity_users');
        $this->addSql('DROP TABLE product_versions');
        $this->addSql('DROP TABLE products');
        $this->addSql('DROP TABLE vulnerabilities');
    }
}
