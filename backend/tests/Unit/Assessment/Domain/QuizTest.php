<?php

declare(strict_types=1);

use App\Assessment\Domain\Model\Question;
use App\Assessment\Domain\Model\QuestionType;
use App\Assessment\Domain\Model\Quiz;
use App\Assessment\Domain\Model\QuizStatus;
use App\Assessment\Domain\Model\QuizType;

describe('Quiz', function () {
    it('creates quiz with default values', function () {
        $quiz = Quiz::create(
            title: 'PHP Basics',
            slug: 'php-basics',
            description: 'A quiz about PHP',
            type: QuizType::Quiz,
        );

        expect($quiz->getId())->not->toBeNull();
        expect($quiz->getTitle())->toBe('PHP Basics');
        expect($quiz->getSlug())->toBe('php-basics');
        expect($quiz->getDescription())->toBe('A quiz about PHP');
        expect($quiz->getType())->toBe(QuizType::Quiz);
        expect($quiz->getStatus())->toBe(QuizStatus::Draft);
        expect($quiz->getStartsAt())->toBeNull();
        expect($quiz->getEndsAt())->toBeNull();
        expect($quiz->getTimeLimit())->toBeNull();
        expect($quiz->getAuthorId())->toBe('');
        expect($quiz->getQuestionCount())->toBe(0);
        expect($quiz->getCreatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
        expect($quiz->getUpdatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('creates quiz with all params', function () {
        $starts = new \DateTimeImmutable('2026-04-01');
        $ends = new \DateTimeImmutable('2026-04-30');

        $quiz = Quiz::create(
            title: 'Advanced PHP',
            slug: 'advanced-php',
            description: 'Hard questions',
            type: QuizType::Survey,
            status: QuizStatus::Published,
            startsAt: $starts,
            endsAt: $ends,
            timeLimit: 3600,
            authorId: 'author-1',
        );

        expect($quiz->getType())->toBe(QuizType::Survey);
        expect($quiz->getStatus())->toBe(QuizStatus::Published);
        expect($quiz->getStartsAt())->toBe($starts);
        expect($quiz->getEndsAt())->toBe($ends);
        expect($quiz->getTimeLimit())->toBe(3600);
        expect($quiz->getAuthorId())->toBe('author-1');
    });

    it('updates only provided fields', function () {
        $quiz = Quiz::create(
            title: 'Original',
            slug: 'original',
            description: 'Desc',
            type: QuizType::Quiz,
        );
        $oldUpdatedAt = $quiz->getUpdatedAt();

        \usleep(1000);
        $quiz->update(title: 'Updated Title');

        expect($quiz->getTitle())->toBe('Updated Title');
        expect($quiz->getSlug())->toBe('original');
        expect($quiz->getDescription())->toBe('Desc');
    });

    it('updates all fields', function () {
        $quiz = Quiz::create(
            title: 'Old',
            slug: 'old',
            description: 'Old desc',
            type: QuizType::Quiz,
        );
        $newStart = new \DateTimeImmutable('2026-05-01');
        $newEnd = new \DateTimeImmutable('2026-05-31');

        $quiz->update(
            title: 'New',
            slug: 'new',
            description: 'New desc',
            type: QuizType::Survey,
            status: QuizStatus::Archived,
            startsAt: $newStart,
            endsAt: $newEnd,
            timeLimit: 1800,
        );

        expect($quiz->getTitle())->toBe('New');
        expect($quiz->getSlug())->toBe('new');
        expect($quiz->getDescription())->toBe('New desc');
        expect($quiz->getType())->toBe(QuizType::Survey);
        expect($quiz->getStatus())->toBe(QuizStatus::Archived);
        expect($quiz->getStartsAt())->toBe($newStart);
        expect($quiz->getEndsAt())->toBe($newEnd);
        expect($quiz->getTimeLimit())->toBe(1800);
    });

    it('adds and removes questions', function () {
        $quiz = Quiz::create(
            title: 'Test',
            slug: 'test',
            description: 'Test desc',
            type: QuizType::Quiz,
        );
        $question = Question::create(
            type: QuestionType::SingleChoice,
            content: 'What is PHP?',
            level: \App\Assessment\Domain\Model\QuestionLevel::Easy,
            score: 10,
            position: 1,
            quiz: $quiz,
        );

        $quiz->addQuestion($question);
        expect($quiz->getQuestionCount())->toBe(1);
        expect($quiz->getQuestions()->contains($question))->toBeTrue();

        $quiz->addQuestion($question);
        expect($quiz->getQuestionCount())->toBe(1);

        $quiz->removeQuestion($question);
        expect($quiz->getQuestionCount())->toBe(0);
    });
});
