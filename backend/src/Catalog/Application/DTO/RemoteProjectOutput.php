<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

use App\Catalog\Domain\Model\RemoteProject;

final readonly class RemoteProjectOutput
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
        public bool $alreadyImported,
    ) {
    }

    public static function fromRemoteProject(RemoteProject $remote, bool $alreadyImported): self
    {
        return new self(
            externalId: $remote->externalId,
            name: $remote->name,
            slug: $remote->slug,
            description: $remote->description,
            repositoryUrl: $remote->repositoryUrl,
            defaultBranch: $remote->defaultBranch,
            visibility: $remote->visibility,
            avatarUrl: $remote->avatarUrl,
            alreadyImported: $alreadyImported,
        );
    }
}
