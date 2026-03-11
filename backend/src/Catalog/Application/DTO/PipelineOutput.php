<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

use App\Catalog\Domain\Model\Pipeline;

final readonly class PipelineOutput
{
    public function __construct(
        public string $id,
        public string $externalId,
        public string $ref,
        public string $status,
        public int $duration,
        public string $startedAt,
        public ?string $finishedAt,
        public string $projectId,
        public string $createdAt,
    ) {
    }

    public static function fromEntity(Pipeline $pipeline): self
    {
        return new self(
            id: $pipeline->getId()->toRfc4122(),
            externalId: $pipeline->getExternalId(),
            ref: $pipeline->getRef(),
            status: $pipeline->getStatus()->value,
            duration: $pipeline->getDuration(),
            startedAt: $pipeline->getStartedAt()->format(\DateTimeInterface::ATOM),
            finishedAt: $pipeline->getFinishedAt()?->format(\DateTimeInterface::ATOM),
            projectId: $pipeline->getProject()->getId()->toRfc4122(),
            createdAt: $pipeline->getCreatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
