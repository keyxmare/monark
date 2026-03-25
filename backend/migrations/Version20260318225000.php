<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260318225000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create dependency_versions table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE dependency_versions (id UUID NOT NULL, dependency_name VARCHAR(255) NOT NULL, package_manager VARCHAR(255) NOT NULL, version VARCHAR(100) NOT NULL, release_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_lts BOOLEAN NOT NULL, is_latest BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_dep_version ON dependency_versions (dependency_name, package_manager, version)');
        $this->addSql('CREATE INDEX idx_dep_version_lookup ON dependency_versions (dependency_name, package_manager)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE dependency_versions');
    }
}
