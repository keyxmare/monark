<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260311175622 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add foreign key constraint on dependencies.project_id to catalog_projects';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dependencies ADD CONSTRAINT FK_EAaborC166D1F9C FOREIGN KEY (project_id) REFERENCES catalog_projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_dependencies_project_id ON dependencies (project_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dependencies DROP CONSTRAINT FK_EAaborC166D1F9C');
        $this->addSql('DROP INDEX IDX_dependencies_project_id');
    }
}
