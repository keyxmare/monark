<?php

declare(strict_types=1);

namespace App\Assessment\Application\QueryHandler;

use App\Assessment\Application\DTO\AttemptOutput;
use App\Assessment\Application\Query\GetAttemptQuery;
use App\Assessment\Domain\Repository\AttemptRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetAttemptHandler
{
    public function __construct(
        private AttemptRepositoryInterface $attemptRepository,
    ) {
    }

    public function __invoke(GetAttemptQuery $query): AttemptOutput
    {
        $attempt = $this->attemptRepository->findById(Uuid::fromString($query->attemptId));
        if ($attempt === null) {
            throw NotFoundException::forEntity('Attempt', $query->attemptId);
        }

        return AttemptOutput::fromEntity($attempt);
    }
}
