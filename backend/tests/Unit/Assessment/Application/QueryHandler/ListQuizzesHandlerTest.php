<?php

declare(strict_types=1);

use App\Assessment\Application\DTO\QuizListOutput;
use App\Assessment\Application\Query\ListQuizzesQuery;
use App\Assessment\Application\QueryHandler\ListQuizzesHandler;
use App\Assessment\Domain\Model\Quiz;
use App\Assessment\Domain\Model\QuizType;
use App\Assessment\Domain\Repository\QuizRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubListQuizzesRepo(array $quizzes = [], int $count = 0): QuizRepositoryInterface
{
    return new class ($quizzes, $count) implements QuizRepositoryInterface {
        public function __construct(private readonly array $quizzes, private readonly int $count)
        {
        }
        public function findById(Uuid $id): ?Quiz
        {
            return null;
        }
        public function findBySlug(string $slug): ?Quiz
        {
            return null;
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return $this->quizzes;
        }
        public function count(): int
        {
            return $this->count;
        }
        public function save(Quiz $quiz): void
        {
        }
        public function delete(Quiz $quiz): void
        {
        }
    };
}

describe('ListQuizzesHandler', function () {
    it('returns paginated quizzes', function () {
        $quiz1 = Quiz::create(title: 'PHP', slug: 'php', description: 'PHP quiz', type: QuizType::Quiz);
        $quiz2 = Quiz::create(title: 'JS', slug: 'js', description: 'JS quiz', type: QuizType::Survey);

        $handler = new ListQuizzesHandler(\stubListQuizzesRepo([$quiz1, $quiz2], 2));
        $result = $handler(new ListQuizzesQuery(1, 20));

        expect($result)->toBeInstanceOf(QuizListOutput::class);
        expect($result->pagination->items)->toHaveCount(2);
        expect($result->pagination->total)->toBe(2);
    });

    it('returns empty list when no quizzes', function () {
        $handler = new ListQuizzesHandler(\stubListQuizzesRepo([], 0));
        $result = $handler(new ListQuizzesQuery());

        expect($result->pagination->items)->toBeEmpty();
        expect($result->pagination->total)->toBe(0);
    });
});
