<?php

declare(strict_types=1);

namespace App\Catalog\Application\Mapper;

use App\Catalog\Application\DTO\ProjectOutput;
use App\Catalog\Application\DTO\TechStackSummaryDTO;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\TechStack;
use DateTimeInterface;

final class ProjectMapper
{
    public static function toOutput(Project $project): ProjectOutput
    {
        return new ProjectOutput(
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
            techStacks: \array_values($project->getTechStacks()->filter(
                static fn (TechStack $ts) => $ts->getFramework() !== '' && $ts->getFramework() !== 'none',
            )->map(
                static fn (TechStack $ts) => new TechStackSummaryDTO(
                    language: $ts->getLanguage(),
                    framework: $ts->getFramework(),
                )
            )->toArray()),
            createdAt: $project->getCreatedAt()->format(DateTimeInterface::ATOM),
            updatedAt: $project->getUpdatedAt()->format(DateTimeInterface::ATOM),
        );
    }
}
