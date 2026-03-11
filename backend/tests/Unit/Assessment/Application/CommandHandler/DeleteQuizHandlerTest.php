<?php

declare(strict_types=1);

use App\Assessment\Application\Command\DeleteQuizCommand;
use App\Assessment\Application\CommandHandler\DeleteQuizHandler;
use App\Assessment\Domain\Model\Quiz;
use App\Assessment\Domain\Model\QuizType;
use App\Assessment\Domain\Repository\QuizRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;

function stubDeleteQuizRepo(?Quiz $quiz = null): QuizRepositoryInterface
{
    return new class ($quiz) implements QuizRepositoryInterface {
        public bool $deleted = false;
        public function __construct(private readonly ?Quiz $quiz) {}
        public function findById(Uuid $id): ?Quiz { return $this->quiz; }
        public function findBySlug(string $slug): ?Quiz { return null; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function count(): int { return 0; }
        public function save(Quiz $quiz): void {}
        public function delete(Quiz $quiz): void { $this->deleted = true; }
    };
}

describe('DeleteQuizHandler', function () {
    it('deletes a quiz successfully', function () {
        $quiz = Quiz::create(
            title: 'PHP Fundamentals',
            slug: 'php-fundamentals',
            description: 'A quiz',
            type: QuizType::Quiz,
        );
        $repo = stubDeleteQuizRepo($quiz);
        $handler = new DeleteQuizHandler($repo);

        $handler(new DeleteQuizCommand($quiz->getId()->toRfc4122()));

        expect($repo->deleted)->toBeTrue();
    });

    it('throws not found when quiz does not exist', function () {
        $handler = new DeleteQuizHandler(stubDeleteQuizRepo(null));
        $handler(new DeleteQuizCommand('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});
