<?php

declare(strict_types=1);

namespace Tests\Factory\Catalog;

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use Symfony\Component\Uid\Uuid;

final class ProjectFactory
{
    public static function create(
        string $name = 'My Project',
        string $slug = 'my-project',
        ?string $description = 'A test project',
        string $repositoryUrl = 'https://gitlab.com/test/project',
        string $defaultBranch = 'main',
        ProjectVisibility $visibility = ProjectVisibility::Private,
        ?Uuid $ownerId = null,
    ): Project {
        return Project::create(
            name: $name,
            slug: $slug,
            description: $description,
            repositoryUrl: $repositoryUrl,
            defaultBranch: $defaultBranch,
            visibility: $visibility,
            ownerId: $ownerId ?? Uuid::v7(),
        );
    }
}
