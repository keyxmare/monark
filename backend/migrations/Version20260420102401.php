<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260420102401 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add activity cache fields (languages palette, commits daily series, last commit) to catalog_projects.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE catalog_projects ADD languages_palette JSON NOT NULL DEFAULT '{}'");
        $this->addSql("ALTER TABLE catalog_projects ADD commits_daily_series JSON NOT NULL DEFAULT '[]'");
        $this->addSql('ALTER TABLE catalog_projects ADD last_commit_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE catalog_projects ADD last_commit_sha VARCHAR(40) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog_projects DROP last_commit_sha');
        $this->addSql('ALTER TABLE catalog_projects DROP last_commit_at');
        $this->addSql('ALTER TABLE catalog_projects DROP commits_daily_series');
        $this->addSql('ALTER TABLE catalog_projects DROP languages_palette');
    }
}
