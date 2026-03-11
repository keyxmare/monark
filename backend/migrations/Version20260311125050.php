<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260311125050 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activity_events (id UUID NOT NULL, type VARCHAR(255) NOT NULL, entity_type VARCHAR(255) NOT NULL, entity_id VARCHAR(255) NOT NULL, payload JSON NOT NULL, occurred_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_id VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE assessment_answers (id UUID NOT NULL, content TEXT NOT NULL, is_correct BOOLEAN NOT NULL, position INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, question_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_FA2E57C81E27F6BF ON assessment_answers (question_id)');
        $this->addSql('CREATE TABLE assessment_attempts (id UUID NOT NULL, score INT NOT NULL, status VARCHAR(255) NOT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, finished_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, user_id VARCHAR(36) NOT NULL, quiz_id VARCHAR(36) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE assessment_questions (id UUID NOT NULL, type VARCHAR(255) NOT NULL, content TEXT NOT NULL, level VARCHAR(255) NOT NULL, score INT NOT NULL, position INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, quiz_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_58ADA739853CD175 ON assessment_questions (quiz_id)');
        $this->addSql('CREATE TABLE assessment_quizzes (id UUID NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description TEXT NOT NULL, type VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, starts_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, ends_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, time_limit INT DEFAULT NULL, author_id VARCHAR(36) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_quiz_slug ON assessment_quizzes (slug)');
        $this->addSql('CREATE TABLE catalog_pipelines (id UUID NOT NULL, external_id VARCHAR(255) NOT NULL, ref VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, duration INT NOT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, finished_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, project_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_1E41B31F166D1F9C ON catalog_pipelines (project_id)');
        $this->addSql('CREATE TABLE catalog_projects (id UUID NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, repository_url VARCHAR(500) NOT NULL, default_branch VARCHAR(100) NOT NULL, visibility VARCHAR(255) NOT NULL, owner_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_project_slug ON catalog_projects (slug)');
        $this->addSql('CREATE TABLE catalog_tech_stacks (id UUID NOT NULL, language VARCHAR(100) NOT NULL, framework VARCHAR(100) NOT NULL, version VARCHAR(50) NOT NULL, detected_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, project_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_B791AFA3166D1F9C ON catalog_tech_stacks (project_id)');
        $this->addSql('CREATE TABLE dependencies (id UUID NOT NULL, name VARCHAR(255) NOT NULL, current_version VARCHAR(100) NOT NULL, latest_version VARCHAR(100) NOT NULL, lts_version VARCHAR(100) NOT NULL, package_manager VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, is_outdated BOOLEAN NOT NULL, project_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE identity_access_tokens (id UUID NOT NULL, provider VARCHAR(255) NOT NULL, token TEXT NOT NULL, scopes JSON NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_43EC9D8BA76ED395 ON identity_access_tokens (user_id)');
        $this->addSql('CREATE TABLE identity_teams (id UUID NOT NULL, name VARCHAR(150) NOT NULL, slug VARCHAR(150) NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_team_slug ON identity_teams (slug)');
        $this->addSql('CREATE TABLE identity_team_members (team_id UUID NOT NULL, user_id UUID NOT NULL, PRIMARY KEY (team_id, user_id))');
        $this->addSql('CREATE INDEX IDX_8665EF9D296CD8AE ON identity_team_members (team_id)');
        $this->addSql('CREATE INDEX IDX_8665EF9DA76ED395 ON identity_team_members (user_id)');
        $this->addSql('CREATE TABLE identity_users (id UUID NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, avatar VARCHAR(255) DEFAULT NULL, roles JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_email ON identity_users (email)');
        $this->addSql('CREATE TABLE notifications (id UUID NOT NULL, title VARCHAR(255) NOT NULL, message TEXT NOT NULL, channel VARCHAR(255) NOT NULL, read_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, user_id VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE vulnerabilities (id UUID NOT NULL, cve_id VARCHAR(50) NOT NULL, severity VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, description TEXT NOT NULL, patched_version VARCHAR(100) NOT NULL, status VARCHAR(255) NOT NULL, detected_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, dependency_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_459DFAF7C2F67723 ON vulnerabilities (dependency_id)');
        $this->addSql('ALTER TABLE assessment_answers ADD CONSTRAINT FK_FA2E57C81E27F6BF FOREIGN KEY (question_id) REFERENCES assessment_questions (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE assessment_questions ADD CONSTRAINT FK_58ADA739853CD175 FOREIGN KEY (quiz_id) REFERENCES assessment_quizzes (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE catalog_pipelines ADD CONSTRAINT FK_1E41B31F166D1F9C FOREIGN KEY (project_id) REFERENCES catalog_projects (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE catalog_tech_stacks ADD CONSTRAINT FK_B791AFA3166D1F9C FOREIGN KEY (project_id) REFERENCES catalog_projects (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE identity_access_tokens ADD CONSTRAINT FK_43EC9D8BA76ED395 FOREIGN KEY (user_id) REFERENCES identity_users (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE identity_team_members ADD CONSTRAINT FK_8665EF9D296CD8AE FOREIGN KEY (team_id) REFERENCES identity_teams (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE identity_team_members ADD CONSTRAINT FK_8665EF9DA76ED395 FOREIGN KEY (user_id) REFERENCES identity_users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE vulnerabilities ADD CONSTRAINT FK_459DFAF7C2F67723 FOREIGN KEY (dependency_id) REFERENCES dependencies (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE assessment_answers DROP CONSTRAINT FK_FA2E57C81E27F6BF');
        $this->addSql('ALTER TABLE assessment_questions DROP CONSTRAINT FK_58ADA739853CD175');
        $this->addSql('ALTER TABLE catalog_pipelines DROP CONSTRAINT FK_1E41B31F166D1F9C');
        $this->addSql('ALTER TABLE catalog_tech_stacks DROP CONSTRAINT FK_B791AFA3166D1F9C');
        $this->addSql('ALTER TABLE identity_access_tokens DROP CONSTRAINT FK_43EC9D8BA76ED395');
        $this->addSql('ALTER TABLE identity_team_members DROP CONSTRAINT FK_8665EF9D296CD8AE');
        $this->addSql('ALTER TABLE identity_team_members DROP CONSTRAINT FK_8665EF9DA76ED395');
        $this->addSql('ALTER TABLE vulnerabilities DROP CONSTRAINT FK_459DFAF7C2F67723');
        $this->addSql('DROP TABLE activity_events');
        $this->addSql('DROP TABLE assessment_answers');
        $this->addSql('DROP TABLE assessment_attempts');
        $this->addSql('DROP TABLE assessment_questions');
        $this->addSql('DROP TABLE assessment_quizzes');
        $this->addSql('DROP TABLE catalog_pipelines');
        $this->addSql('DROP TABLE catalog_projects');
        $this->addSql('DROP TABLE catalog_tech_stacks');
        $this->addSql('DROP TABLE dependencies');
        $this->addSql('DROP TABLE identity_access_tokens');
        $this->addSql('DROP TABLE identity_teams');
        $this->addSql('DROP TABLE identity_team_members');
        $this->addSql('DROP TABLE identity_users');
        $this->addSql('DROP TABLE notifications');
        $this->addSql('DROP TABLE vulnerabilities');
    }
}
