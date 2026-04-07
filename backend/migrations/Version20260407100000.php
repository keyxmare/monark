<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create history_project_debt_snapshots and history_dependency_snapshots tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE history_project_debt_snapshots (id UUID NOT NULL, project_id UUID NOT NULL, commit_sha VARCHAR(40) NOT NULL, snapshot_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, source VARCHAR(20) NOT NULL, total_deps INT NOT NULL, outdated_count INT NOT NULL, vulnerable_count INT NOT NULL, major_gap_count INT NOT NULL, minor_gap_count INT NOT NULL, patch_gap_count INT NOT NULL, lts_gap_count INT NOT NULL, debt_score DOUBLE PRECISION NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_debt_snap_project_date ON history_project_debt_snapshots (project_id, snapshot_date)');
        $this->addSql('CREATE UNIQUE INDEX uniq_debt_snap_commit ON history_project_debt_snapshots (project_id, commit_sha)');

        $this->addSql('CREATE TABLE history_dependency_snapshots (id UUID NOT NULL, debt_snapshot_id UUID NOT NULL, project_id UUID NOT NULL, snapshot_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, name VARCHAR(255) NOT NULL, package_manager VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, current_version VARCHAR(100) NOT NULL, latest_version_at_date VARCHAR(100) DEFAULT NULL, lts_version_at_date VARCHAR(100) DEFAULT NULL, is_outdated BOOLEAN NOT NULL, gap_type VARCHAR(20) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_dep_snap_project_date ON history_dependency_snapshots (project_id, snapshot_date)');
        $this->addSql('CREATE INDEX idx_dep_snap_name ON history_dependency_snapshots (project_id, name, snapshot_date)');
        $this->addSql('CREATE INDEX IDX_HIST_DEPSNAP_PARENT ON history_dependency_snapshots (debt_snapshot_id)');
        $this->addSql('ALTER TABLE history_dependency_snapshots ADD CONSTRAINT FK_HIST_DEPSNAP_PARENT FOREIGN KEY (debt_snapshot_id) REFERENCES history_project_debt_snapshots (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE history_dependency_snapshots DROP CONSTRAINT FK_HIST_DEPSNAP_PARENT');
        $this->addSql('DROP TABLE history_dependency_snapshots');
        $this->addSql('DROP TABLE history_project_debt_snapshots');
    }
}
