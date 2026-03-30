<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260329233851 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS activity_events');
        $this->addSql('DROP TABLE IF EXISTS identity_access_tokens');
        $this->addSql('DROP TABLE IF EXISTS notifications');
        $this->addSql('DROP TABLE IF EXISTS sync_tasks');
        $this->addSql('ALTER TABLE catalog_tech_stacks ADD IF NOT EXISTS latest_lts VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE catalog_tech_stacks ADD IF NOT EXISTS lts_gap VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE catalog_tech_stacks ADD IF NOT EXISTS maintenance_status VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE catalog_tech_stacks ADD IF NOT EXISTS eol_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE catalog_tech_stacks ADD IF NOT EXISTS version_synced_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE dependencies ALTER registry_status DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activity_events (id UUID NOT NULL, type VARCHAR(255) NOT NULL, entity_type VARCHAR(255) NOT NULL, entity_id VARCHAR(255) NOT NULL, payload JSON NOT NULL, occurred_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_id VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE identity_access_tokens (id UUID NOT NULL, provider VARCHAR(255) NOT NULL, token TEXT NOT NULL, scopes JSON NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_43ec9d8ba76ed395 ON identity_access_tokens (user_id)');
        $this->addSql('CREATE TABLE notifications (id UUID NOT NULL, title VARCHAR(255) NOT NULL, message TEXT NOT NULL, channel VARCHAR(255) NOT NULL, read_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, user_id VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE sync_tasks (id UUID NOT NULL, type VARCHAR(255) NOT NULL, severity VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, description TEXT NOT NULL, status VARCHAR(255) NOT NULL, metadata JSON NOT NULL, project_id UUID NOT NULL, resolved_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_sync_task_project_type_status ON sync_tasks (project_id, type, status)');
        $this->addSql('ALTER TABLE identity_access_tokens ADD CONSTRAINT fk_43ec9d8ba76ed395 FOREIGN KEY (user_id) REFERENCES identity_users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE catalog_tech_stacks DROP latest_lts');
        $this->addSql('ALTER TABLE catalog_tech_stacks DROP lts_gap');
        $this->addSql('ALTER TABLE catalog_tech_stacks DROP maintenance_status');
        $this->addSql('ALTER TABLE catalog_tech_stacks DROP eol_date');
        $this->addSql('ALTER TABLE catalog_tech_stacks DROP version_synced_at');
        $this->addSql('ALTER TABLE dependencies ALTER registry_status SET DEFAULT \'pending\'');
    }
}
