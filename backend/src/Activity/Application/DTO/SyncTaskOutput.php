<?php

declare(strict_types=1);

namespace App\Activity\Application\DTO;

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
}
