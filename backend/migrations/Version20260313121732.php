<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313121732 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop catalog_pipelines table (unused feature removed)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog_pipelines DROP CONSTRAINT FK_1E41B31F166D1F9C');
        $this->addSql('DROP TABLE catalog_pipelines');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE catalog_pipelines (id UUID NOT NULL, external_id VARCHAR(255) NOT NULL, ref VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, duration INT NOT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, finished_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, project_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_1E41B31F166D1F9C ON catalog_pipelines (project_id)');
        $this->addSql('ALTER TABLE catalog_pipelines ADD CONSTRAINT FK_1E41B31F166D1F9C FOREIGN KEY (project_id) REFERENCES catalog_projects (id) ON DELETE CASCADE NOT DEFERRABLE');
    }
}
