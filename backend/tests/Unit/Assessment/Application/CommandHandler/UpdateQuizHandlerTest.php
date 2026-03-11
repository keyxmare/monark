<?php

declare(strict_types=1);

use App\Assessment\Application\Command\UpdateQuizCommand;
use App\Assessment\Application\CommandHandler\UpdateQuizHandler;
use App\Assessment\Application\DTO\QuizOutput;
use App\Assessment\Application\DTO\UpdateQuizInput;
use App\Assessment\Domain\Model\Quiz;
use App\Assessment\Domain\Model\QuizType;
use App\Assessment\Domain\Repository\QuizRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;

function stubUpdateQuizRepo(?Quiz $quiz = null): QuizRepositoryInterface
{
    return new class ($quiz) implements QuizRepositoryInterface {
        public ?Quiz $saved = null;
        public function __construct(private readonly ?Quiz $quiz) {}
        public function findById(Uuid $id): ?Quiz { return $this->quiz; }
        public function findBySlug(string $slug): ?Quiz { return null; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function count(): int { return 0; }
        public function save(Quiz $quiz): void { $this->saved = $quiz; }
        public function delete(Quiz $quiz): void {}
    };
}

describe('UpdateQuizHandler', function () {
    it('updates a quiz successfully', function () {
        $quiz = Quiz::create(
            title: 'PHP Fundamentals',
            slug: 'php-fundamentals',
            description: 'A quiz',
            type: QuizType::Quiz,
        );
        $quizId = $quiz->getId()->toRfc4122();

        $repo = stubUpdateQuizRepo($quiz);
        $handler = new UpdateQuizHandler($repo);

        $input = new UpdateQuizInput(title: 'Advanced PHP', description: 'An advanced quiz');
        $result = $handler(new UpdateQuizCommand($quizId, $input));

        expect($result)->toBeInstanceOf(QuizOutput::class);
        expect($result->title)->toBe('Advanced PHP');
        expect($result->description)->toBe('An advanced quiz');
        expect($repo->saved)->not->toBeNull();
    });

    it('throws not found when quiz does not exist', function () {
        $handler = new UpdateQuizHandler(stubUpdateQuizRepo(null));
        $input = new UpdateQuizInput(title: 'New Title');
        $handler(new UpdateQuizCommand('00000000-0000-0000-0000-000000000000', $input));
    })->throws(NotFoundException::class);
});
