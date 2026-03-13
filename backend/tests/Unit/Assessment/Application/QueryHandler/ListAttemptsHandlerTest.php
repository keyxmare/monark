<?php

declare(strict_types=1);

use App\Assessment\Application\DTO\AttemptListOutput;
use App\Assessment\Application\Query\ListAttemptsQuery;
use App\Assessment\Application\QueryHandler\ListAttemptsHandler;
use App\Assessment\Domain\Model\Attempt;
use App\Assessment\Domain\Repository\AttemptRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubListAttemptsRepo(array $attempts = [], int $count = 0): AttemptRepositoryInterface
{
    return new class ($attempts, $count) implements AttemptRepositoryInterface {
        public function __construct(private readonly array $attempts, private readonly int $count)
        {
        }
        public function findById(Uuid $id): ?Attempt
        {
            return null;
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return $this->attempts;
        }
        public function count(): int
        {
            return $this->count;
        }
        public function save(Attempt $attempt): void
        {
        }
    };
}

describe('ListAttemptsHandler', function () {
    it('returns paginated attempts', function () {
        $a1 = Attempt::create(userId: 'u1', quizId: 'q1');
        $a2 = Attempt::create(userId: 'u2', quizId: 'q2');

        $handler = new ListAttemptsHandler(\stubListAttemptsRepo([$a1, $a2], 2));
        $result = $handler(new ListAttemptsQuery(1, 20));

        expect($result)->toBeInstanceOf(AttemptListOutput::class);
        expect($result->pagination->items)->toHaveCount(2);
        expect($result->pagination->total)->toBe(2);
    });

    it('returns empty list when no attempts', function () {
        $handler = new ListAttemptsHandler(\stubListAttemptsRepo([], 0));
        $result = $handler(new ListAttemptsQuery());

        expect($result->pagination->items)->toBeEmpty();
        expect($result->pagination->total)->toBe(0);
    });
});
