<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260318192000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop Team tables (identity_team_members, identity_teams)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE identity_team_members DROP CONSTRAINT FK_8665EF9D296CD8AE');
        $this->addSql('ALTER TABLE identity_team_members DROP CONSTRAINT FK_8665EF9DA76ED395');
        $this->addSql('DROP TABLE identity_team_members');
        $this->addSql('DROP TABLE identity_teams');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE identity_teams (id UUID NOT NULL, name VARCHAR(150) NOT NULL, slug VARCHAR(150) NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_team_slug ON identity_teams (slug)');
        $this->addSql('CREATE TABLE identity_team_members (team_id UUID NOT NULL, user_id UUID NOT NULL, PRIMARY KEY (team_id, user_id))');
        $this->addSql('CREATE INDEX IDX_8665EF9D296CD8AE ON identity_team_members (team_id)');
        $this->addSql('CREATE INDEX IDX_8665EF9DA76ED395 ON identity_team_members (user_id)');
        $this->addSql('ALTER TABLE identity_team_members ADD CONSTRAINT FK_8665EF9D296CD8AE FOREIGN KEY (team_id) REFERENCES identity_teams (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE identity_team_members ADD CONSTRAINT FK_8665EF9DA76ED395 FOREIGN KEY (user_id) REFERENCES identity_users (id) ON DELETE CASCADE');
    }
}
