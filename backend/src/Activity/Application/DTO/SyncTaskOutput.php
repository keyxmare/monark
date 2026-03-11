<?php

declare(strict_types=1);

namespace App\Activity\Application\DTO;

use App\Activity\Domain\Model\SyncTask;

final readonly class SyncTaskOutput
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public string $id,
        public string $type,
        public string $severity,
        public string $title,
        public string $description,
        public string $status,
        public array $metadata,
        public string $projectId,
        public ?string $resolvedAt,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(SyncTask $syncTask): self
    {
        return new self(
            id: $syncTask->getId()->toRfc4122(),
            type: $syncTask->getType()->value,
            severity: $syncTask->getSeverity()->value,
            title: $syncTask->getTitle(),
            description: $syncTask->getDescription(),
            status: $syncTask->getStatus()->value,
            metadata: $syncTask->getMetadata(),
            projectId: $syncTask->getProjectId()->toRfc4122(),
            resolvedAt: $syncTask->getResolvedAt()?->format(\DateTimeInterface::ATOM),
            createdAt: $syncTask->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $syncTask->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
