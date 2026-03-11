<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260311151722 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add framework_version column to catalog_tech_stacks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE catalog_tech_stacks ADD framework_version VARCHAR(50) NOT NULL DEFAULT ''");
        $this->addSql('ALTER TABLE catalog_tech_stacks ALTER COLUMN framework_version DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE catalog_tech_stacks DROP framework_version');
    }
}
