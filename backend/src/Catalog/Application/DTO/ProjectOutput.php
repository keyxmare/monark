<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

use App\Catalog\Domain\Model\Project;

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
        public int $techStacksCount,
        public int $pipelinesCount,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(Project $project): self
    {
        return new self(
            id: $project->getId()->toRfc4122(),
            name: $project->getName(),
            slug: $project->getSlug(),
            description: $project->getDescription(),
            repositoryUrl: $project->getRepositoryUrl(),
            defaultBranch: $project->getDefaultBranch(),
            visibility: $project->getVisibility()->value,
            ownerId: $project->getOwnerId()->toRfc4122(),
            providerId: $project->getProvider()?->getId()->toRfc4122(),
            externalId: $project->getExternalId(),
            techStacksCount: $project->getTechStacks()->count(),
            pipelinesCount: $project->getPipelines()->count(),
            createdAt: $project->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $project->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
