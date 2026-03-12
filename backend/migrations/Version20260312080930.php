<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260312080930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE catalog_merge_requests (id UUID NOT NULL, external_id VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, source_branch VARCHAR(255) NOT NULL, target_branch VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, author VARCHAR(255) NOT NULL, url VARCHAR(500) NOT NULL, additions INT DEFAULT NULL, deletions INT DEFAULT NULL, reviewers JSON NOT NULL, labels JSON NOT NULL, merged_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, closed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, project_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_5F29298E166D1F9C ON catalog_merge_requests (project_id)');
        $this->addSql('CREATE INDEX idx_mr_project_status ON catalog_merge_requests (project_id, status)');
        $this->addSql('ALTER TABLE catalog_merge_requests ADD CONSTRAINT FK_5F29298E166D1F9C FOREIGN KEY (project_id) REFERENCES catalog_projects (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE catalog_merge_requests DROP CONSTRAINT FK_5F29298E166D1F9C');
        $this->addSql('DROP TABLE catalog_merge_requests');
    }
}
