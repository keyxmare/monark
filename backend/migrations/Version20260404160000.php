<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260404160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove Language entity — inline language data into Framework and LanguageVulnerability';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog_frameworks ADD COLUMN language_name VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE catalog_frameworks ADD COLUMN language_version VARCHAR(50) DEFAULT NULL');

        $this->addSql(<<<'SQL'
            UPDATE catalog_frameworks f
            SET language_name = l.name, language_version = l.version
            FROM catalog_languages l
            WHERE f.language_id = l.id
        SQL);

        $this->addSql('ALTER TABLE catalog_frameworks ALTER COLUMN language_name SET NOT NULL');
        $this->addSql('ALTER TABLE catalog_frameworks ALTER COLUMN language_version SET NOT NULL');
        $this->addSql('ALTER TABLE catalog_frameworks DROP CONSTRAINT IF EXISTS fk_catalog_frameworks_language_id');
        $this->addSql('ALTER TABLE catalog_frameworks DROP COLUMN language_id');

        $this->addSql('ALTER TABLE catalog_language_vulnerabilities ADD COLUMN language_name VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE catalog_language_vulnerabilities ADD COLUMN language_version VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE catalog_language_vulnerabilities ADD COLUMN project_id UUID DEFAULT NULL');

        $this->addSql(<<<'SQL'
            UPDATE catalog_language_vulnerabilities v
            SET language_name = l.name, language_version = l.version, project_id = l.project_id
            FROM catalog_languages l
            WHERE v.language_id = l.id
        SQL);

        $this->addSql('ALTER TABLE catalog_language_vulnerabilities ALTER COLUMN language_name SET NOT NULL');
        $this->addSql('ALTER TABLE catalog_language_vulnerabilities ALTER COLUMN language_version SET NOT NULL');
        $this->addSql('ALTER TABLE catalog_language_vulnerabilities DROP CONSTRAINT IF EXISTS fk_lang_vuln_language_id');
        $this->addSql('DELETE FROM catalog_language_vulnerabilities WHERE project_id IS NULL');
        $this->addSql('ALTER TABLE catalog_language_vulnerabilities ALTER COLUMN project_id SET NOT NULL');
        $this->addSql('ALTER TABLE catalog_language_vulnerabilities DROP COLUMN language_id');

        $this->addSql('DROP TABLE IF EXISTS catalog_languages');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE catalog_languages (
                id UUID NOT NULL,
                project_id UUID NOT NULL,
                name VARCHAR(100) NOT NULL,
                version VARCHAR(50) NOT NULL,
                detected_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        SQL);
    }
}
