<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260319075500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add registry_status column to dependencies table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE dependencies ADD registry_status VARCHAR(255) NOT NULL DEFAULT 'pending'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dependencies DROP COLUMN registry_status');
    }
}
