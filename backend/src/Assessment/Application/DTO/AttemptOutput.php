<?php

declare(strict_types=1);

namespace App\Assessment\Application\DTO;

use App\Assessment\Domain\Model\Attempt;
use DateTimeInterface;

final readonly class AttemptOutput
{
    public function __construct(
        public string $id,
        public int $score,
        public string $status,
        public string $startedAt,
        public ?string $finishedAt,
        public string $userId,
        public string $quizId,
        public string $createdAt,
    ) {
    }

    public static function fromEntity(Attempt $attempt): self
    {
        return new self(
            id: $attempt->getId()->toRfc4122(),
            score: $attempt->getScore(),
            status: $attempt->getStatus()->value,
            startedAt: $attempt->getStartedAt()->format(DateTimeInterface::ATOM),
            finishedAt: $attempt->getFinishedAt()?->format(DateTimeInterface::ATOM),
            userId: $attempt->getUserId(),
            quizId: $attempt->getQuizId(),
            createdAt: $attempt->getCreatedAt()->format(DateTimeInterface::ATOM),
        );
    }
}
