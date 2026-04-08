<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

final readonly class ProjectOutput
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public string $repositoryUrl,
        public string $defaultBranch,
        public string $visibility,
        public string $ownerId,
        public ?string $providerId,
        public ?string $externalId,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }
}
