<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Model;

final readonly class RemoteProject
{
    public function __construct(
        public string $externalId,
        public string $name,
        public string $slug,
        public ?string $description,
        public string $repositoryUrl,
        public string $defaultBranch,
        public string $visibility,
        public ?string $avatarUrl,
    ) {
    }
}
