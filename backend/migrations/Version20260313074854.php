<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260313074854 extends AbstractMigration
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
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE activity_build_metrics');
    }
}
