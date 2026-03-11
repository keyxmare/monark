<?php

declare(strict_types=1);

use App\Assessment\Application\Command\CreateQuestionCommand;
use App\Assessment\Application\CommandHandler\CreateQuestionHandler;
use App\Assessment\Application\DTO\CreateQuestionInput;
use App\Assessment\Application\DTO\QuestionOutput;
use App\Assessment\Domain\Model\Question;
use App\Assessment\Domain\Model\Quiz;
use App\Assessment\Domain\Model\QuizType;
use App\Assessment\Domain\Repository\QuestionRepositoryInterface;
use App\Assessment\Domain\Repository\QuizRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;

function stubCreateQuestionQuizRepo(?Quiz $quiz = null): QuizRepositoryInterface
{
    return new class ($quiz) implements QuizRepositoryInterface {
        public function __construct(private readonly ?Quiz $quiz) {}
        public function findById(Uuid $id): ?Quiz { return $this->quiz; }
        public function findBySlug(string $slug): ?Quiz { return null; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function count(): int { return 0; }
        public function save(Quiz $quiz): void {}
        public function delete(Quiz $quiz): void {}
    };
}

function stubCreateQuestionRepo(): QuestionRepositoryInterface
{
    return new class implements QuestionRepositoryInterface {
        public ?Question $saved = null;
        public function findById(Uuid $id): ?Question { return null; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function findByQuizId(Uuid $quizId, int $page = 1, int $perPage = 20): array { return []; }
        public function count(): int { return 0; }
        public function countByQuizId(Uuid $quizId): int { return 0; }
        public function save(Question $question): void { $this->saved = $question; }
        public function delete(Question $question): void {}
    };
}

describe('CreateQuestionHandler', function () {
    it('creates a question successfully', function () {
        $quiz = Quiz::create(
            title: 'PHP Fundamentals',
            slug: 'php-fundamentals',
            description: 'A quiz',
            type: QuizType::Quiz,
        );

        $questionRepo = stubCreateQuestionRepo();
        $handler = new CreateQuestionHandler($questionRepo, stubCreateQuestionQuizRepo($quiz));

        $input = new CreateQuestionInput(
            type: 'single_choice',
            content: 'What is PHP?',
            level: 'easy',
            score: 1,
            position: 1,
            quizId: $quiz->getId()->toRfc4122(),
        );

        $result = $handler(new CreateQuestionCommand($input));

        expect($result)->toBeInstanceOf(QuestionOutput::class);
        expect($result->content)->toBe('What is PHP?');
        expect($result->type)->toBe('single_choice');
        expect($questionRepo->saved)->not->toBeNull();
    });

    it('throws not found when quiz does not exist', function () {
        $handler = new CreateQuestionHandler(stubCreateQuestionRepo(), stubCreateQuestionQuizRepo(null));

        $input = new CreateQuestionInput(
            type: 'single_choice',
            content: 'What is PHP?',
            level: 'easy',
            score: 1,
            position: 1,
            quizId: '00000000-0000-0000-0000-000000000000',
        );
        $handler(new CreateQuestionCommand($input));
    })->throws(NotFoundException::class);
});
