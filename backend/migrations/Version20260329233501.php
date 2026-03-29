<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260329233501 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Clear DC2Type comments from products and product_versions columns (not needed with native types)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("COMMENT ON COLUMN products.id IS ''");
        $this->addSql("COMMENT ON COLUMN products.last_synced_at IS ''");
        $this->addSql("COMMENT ON COLUMN products.created_at IS ''");
        $this->addSql("COMMENT ON COLUMN product_versions.id IS ''");
        $this->addSql("COMMENT ON COLUMN product_versions.release_date IS ''");
        $this->addSql("COMMENT ON COLUMN product_versions.created_at IS ''");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("COMMENT ON COLUMN products.id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN products.last_synced_at IS '(DC2Type:datetime_immutable)'");
        $this->addSql("COMMENT ON COLUMN products.created_at IS '(DC2Type:datetime_immutable)'");
        $this->addSql("COMMENT ON COLUMN product_versions.id IS '(DC2Type:uuid)'");
        $this->addSql("COMMENT ON COLUMN product_versions.release_date IS '(DC2Type:datetime_immutable)'");
        $this->addSql("COMMENT ON COLUMN product_versions.created_at IS '(DC2Type:datetime_immutable)'");
    }
}
