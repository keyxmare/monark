<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

final readonly class ProviderOutput
{
    public function __construct(
        public string $id,
        public string $name,
        public string $type,
        public string $url,
        public ?string $username,
        public string $status,
        public int $projectsCount,
        public ?string $lastSyncAt,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }
}
