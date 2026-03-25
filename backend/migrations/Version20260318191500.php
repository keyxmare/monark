<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260318191500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop Assessment tables (quiz, question, answer, attempt)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE assessment_answers DROP CONSTRAINT FK_FA2E57C81E27F6BF');
        $this->addSql('ALTER TABLE assessment_questions DROP CONSTRAINT FK_58ADA739853CD175');
        $this->addSql('DROP TABLE assessment_answers');
        $this->addSql('DROP TABLE assessment_attempts');
        $this->addSql('DROP TABLE assessment_questions');
        $this->addSql('DROP TABLE assessment_quizzes');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE assessment_quizzes (id UUID NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description TEXT NOT NULL, type VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, starts_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, ends_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, time_limit INT DEFAULT NULL, author_id VARCHAR(36) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_quiz_slug ON assessment_quizzes (slug)');
        $this->addSql('CREATE TABLE assessment_questions (id UUID NOT NULL, type VARCHAR(255) NOT NULL, content TEXT NOT NULL, level VARCHAR(255) NOT NULL, score INT NOT NULL, position INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, quiz_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_58ADA739853CD175 ON assessment_questions (quiz_id)');
        $this->addSql('CREATE TABLE assessment_answers (id UUID NOT NULL, content TEXT NOT NULL, is_correct BOOLEAN NOT NULL, position INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, question_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_FA2E57C81E27F6BF ON assessment_answers (question_id)');
        $this->addSql('CREATE TABLE assessment_attempts (id UUID NOT NULL, score INT NOT NULL, status VARCHAR(255) NOT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, finished_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, user_id VARCHAR(36) NOT NULL, quiz_id VARCHAR(36) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE assessment_answers ADD CONSTRAINT FK_FA2E57C81E27F6BF FOREIGN KEY (question_id) REFERENCES assessment_questions (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE assessment_questions ADD CONSTRAINT FK_58ADA739853CD175 FOREIGN KEY (quiz_id) REFERENCES assessment_quizzes (id) ON DELETE CASCADE NOT DEFERRABLE');
    }
}
