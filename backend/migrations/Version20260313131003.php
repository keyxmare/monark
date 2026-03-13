<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313131003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove FK constraint on dependencies.project_id (cross-context decoupling)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dependencies DROP CONSTRAINT fk_eaaborc166d1f9c');
        $this->addSql('DROP INDEX idx_ea0f708d166d1f9c');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dependencies ADD CONSTRAINT fk_eaaborc166d1f9c FOREIGN KEY (project_id) REFERENCES catalog_projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_ea0f708d166d1f9c ON dependencies (project_id)');
    }
}
