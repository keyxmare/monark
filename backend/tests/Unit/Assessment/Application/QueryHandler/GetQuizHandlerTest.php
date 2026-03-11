<?php

declare(strict_types=1);

use App\Assessment\Application\DTO\QuizOutput;
use App\Assessment\Application\Query\GetQuizQuery;
use App\Assessment\Application\QueryHandler\GetQuizHandler;
use App\Assessment\Domain\Model\Quiz;
use App\Assessment\Domain\Model\QuizType;
use App\Assessment\Domain\Repository\QuizRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;

function stubGetQuizRepo(?Quiz $quiz = null): QuizRepositoryInterface
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

describe('GetQuizHandler', function () {
    it('returns a quiz by id', function () {
        $quiz = Quiz::create(
            title: 'PHP Fundamentals',
            slug: 'php-fundamentals',
            description: 'A quiz about PHP basics',
            type: QuizType::Quiz,
        );
        $handler = new GetQuizHandler(stubGetQuizRepo($quiz));
        $result = $handler(new GetQuizQuery($quiz->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(QuizOutput::class);
        expect($result->title)->toBe('PHP Fundamentals');
        expect($result->type)->toBe('quiz');
    });

    it('throws not found when quiz does not exist', function () {
        $handler = new GetQuizHandler(stubGetQuizRepo(null));
        $handler(new GetQuizQuery('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});
