<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260329233500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create products and product_versions tables for the VersionRegistry bounded context';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE products (
            id UUID NOT NULL,
            name VARCHAR(255) NOT NULL,
            type VARCHAR(255) NOT NULL,
            package_manager VARCHAR(255) DEFAULT NULL,
            resolver_source VARCHAR(255) NOT NULL,
            latest_version VARCHAR(100) DEFAULT NULL,
            lts_version VARCHAR(100) DEFAULT NULL,
            last_synced_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY (id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_product ON products (name, package_manager)');
        $this->addSql('CREATE TABLE product_versions (
            id UUID NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            package_manager VARCHAR(255) DEFAULT NULL,
            version VARCHAR(100) NOT NULL,
            release_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            is_lts BOOLEAN NOT NULL,
            is_latest BOOLEAN NOT NULL,
            eol_date VARCHAR(20) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY (id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_product_version ON product_versions (product_name, package_manager, version)');
        $this->addSql('CREATE INDEX idx_product_version_lookup ON product_versions (product_name, package_manager)');
        $this->addSql('CREATE INDEX idx_product_version_latest ON product_versions (product_name, package_manager, is_latest)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE product_versions');
        $this->addSql('DROP TABLE products');
    }
}
