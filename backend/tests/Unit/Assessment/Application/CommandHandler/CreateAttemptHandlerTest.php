<?php

declare(strict_types=1);

use App\Assessment\Application\Command\CreateAttemptCommand;
use App\Assessment\Application\CommandHandler\CreateAttemptHandler;
use App\Assessment\Application\DTO\AttemptOutput;
use App\Assessment\Application\DTO\CreateAttemptInput;
use App\Assessment\Domain\Model\Attempt;
use App\Assessment\Domain\Repository\AttemptRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubCreateAttemptRepo(): AttemptRepositoryInterface
{
    return new class implements AttemptRepositoryInterface {
        public ?Attempt $saved = null;
        public function findById(Uuid $id): ?Attempt { return null; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function count(): int { return 0; }
        public function save(Attempt $attempt): void { $this->saved = $attempt; }
    };
}

describe('CreateAttemptHandler', function () {
    it('creates an attempt successfully', function () {
        $repo = stubCreateAttemptRepo();
        $handler = new CreateAttemptHandler($repo);

        $input = new CreateAttemptInput(
            userId: '00000000-0000-0000-0000-000000000001',
            quizId: '00000000-0000-0000-0000-000000000002',
        );

        $result = $handler(new CreateAttemptCommand($input));

        expect($result)->toBeInstanceOf(AttemptOutput::class);
        expect($result->userId)->toBe('00000000-0000-0000-0000-000000000001');
        expect($result->quizId)->toBe('00000000-0000-0000-0000-000000000002');
        expect($result->status)->toBe('started');
        expect($result->score)->toBe(0);
        expect($repo->saved)->not->toBeNull();
    });
});
