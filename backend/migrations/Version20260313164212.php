<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313164212 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove invalid Docker tech stacks (image tags stored as frameworks)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM catalog_tech_stacks WHERE language = 'Docker'");
    }

    public function down(Schema $schema): void
    {
    }
}
