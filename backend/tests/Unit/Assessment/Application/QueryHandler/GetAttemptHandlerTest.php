<?php

declare(strict_types=1);

use App\Assessment\Application\DTO\AttemptOutput;
use App\Assessment\Application\Query\GetAttemptQuery;
use App\Assessment\Application\QueryHandler\GetAttemptHandler;
use App\Assessment\Domain\Model\Attempt;
use App\Assessment\Domain\Repository\AttemptRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;

function stubGetAttemptRepo(?Attempt $attempt = null): AttemptRepositoryInterface
{
    return new class ($attempt) implements AttemptRepositoryInterface {
        public function __construct(private readonly ?Attempt $attempt)
        {
        }
        public function findById(Uuid $id): ?Attempt
        {
            return $this->attempt;
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function count(): int
        {
            return 0;
        }
        public function save(Attempt $attempt): void
        {
        }
    };
}

describe('GetAttemptHandler', function () {
    it('returns an attempt by id', function () {
        $attempt = Attempt::create(
            userId: '00000000-0000-0000-0000-000000000001',
            quizId: '00000000-0000-0000-0000-000000000002',
        );
        $handler = new GetAttemptHandler(\stubGetAttemptRepo($attempt));
        $result = $handler(new GetAttemptQuery($attempt->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(AttemptOutput::class);
        expect($result->status)->toBe('started');
        expect($result->userId)->toBe('00000000-0000-0000-0000-000000000001');
    });

    it('throws not found when attempt does not exist', function () {
        $handler = new GetAttemptHandler(\stubGetAttemptRepo(null));
        $handler(new GetAttemptQuery('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});
