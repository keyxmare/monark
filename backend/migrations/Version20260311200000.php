<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260311200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add repository_url column to dependencies table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dependencies ADD repository_url VARCHAR(2048) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dependencies DROP COLUMN repository_url');
    }
}
