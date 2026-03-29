<?php

declare(strict_types=1);

namespace App\Activity\Application\DTO;

final readonly class ActivityEventOutput
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $id,
        public string $type,
        public string $entityType,
        public string $entityId,
        public array $payload,
        public string $occurredAt,
        public string $userId,
    ) {
    }
}
