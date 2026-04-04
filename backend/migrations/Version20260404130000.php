<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260404130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop catalog_merge_requests table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS catalog_merge_requests');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE catalog_merge_requests (
                id UUID NOT NULL,
                project_id UUID NOT NULL,
                remote_id INT NOT NULL,
                title VARCHAR(512) NOT NULL,
                status VARCHAR(32) NOT NULL,
                source_branch VARCHAR(255) NOT NULL,
                target_branch VARCHAR(255) NOT NULL,
                author VARCHAR(255) NOT NULL,
                url VARCHAR(1024) NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                merged_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY(id)
            )
        SQL);
        $this->addSql('CREATE INDEX idx_merge_requests_project ON catalog_merge_requests (project_id)');
    }
}
