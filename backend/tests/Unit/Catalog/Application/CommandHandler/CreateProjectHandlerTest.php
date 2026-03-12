<?php

declare(strict_types=1);

use App\Catalog\Application\Command\CreateProjectCommand;
use App\Catalog\Application\CommandHandler\CreateProjectHandler;
use App\Catalog\Application\DTO\CreateProjectInput;
use App\Catalog\Application\DTO\ProjectOutput;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubCreateProjectRepo(?Project $findBySlugResult = null): ProjectRepositoryInterface
{
    return new class ($findBySlugResult) implements ProjectRepositoryInterface {
        public ?Project $saved = null;
        public function __construct(private readonly ?Project $findBySlugResult) {}
        public function findById(Uuid $id): ?Project { return null; }
        public function findBySlug(string $slug): ?Project { return $this->findBySlugResult; }
        public function findByExternalIdAndProvider(string $externalId, Uuid $providerId): ?Project { return null; }
        public function findExternalIdMapByProvider(Uuid $providerId): array { return []; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function findByProviderId(Uuid $providerId): array { return []; }
        public function findAllWithProvider(): array { return []; }
        public function count(): int { return 0; }
        public function save(Project $project): void { $this->saved = $project; }
        public function delete(Project $project): void {}
    };
}

describe('CreateProjectHandler', function () {
    it('creates a project successfully', function () {
        $repo = stubCreateProjectRepo(null);
        $handler = new CreateProjectHandler($repo);

        $ownerId = Uuid::v7()->toRfc4122();
        $input = new CreateProjectInput(
            name: 'My Project',
            slug: 'my-project',
            description: 'A test project',
            repositoryUrl: 'https://gitlab.com/test/project',
            defaultBranch: 'main',
            visibility: ProjectVisibility::Private,
            ownerId: $ownerId,
        );

        $result = $handler(new CreateProjectCommand($input));

        expect($result)->toBeInstanceOf(ProjectOutput::class);
        expect($result->name)->toBe('My Project');
        expect($result->slug)->toBe('my-project');
        expect($result->visibility)->toBe('private');
        expect($result->ownerId)->toBe($ownerId);
        expect($repo->saved)->not->toBeNull();
    });

    it('throws exception when slug already exists', function () {
        $existingProject = Project::create(
            name: 'Existing',
            slug: 'my-project',
            description: null,
            repositoryUrl: 'https://gitlab.com/test/existing',
            defaultBranch: 'main',
            visibility: ProjectVisibility::Private,
            ownerId: Uuid::v7(),
        );
        $handler = new CreateProjectHandler(stubCreateProjectRepo($existingProject));

        $input = new CreateProjectInput(
            name: 'My Project',
            slug: 'my-project',
            repositoryUrl: 'https://gitlab.com/test/project',
            ownerId: Uuid::v7()->toRfc4122(),
        );
        $handler(new CreateProjectCommand($input));
    })->throws(\DomainException::class, 'A project with this slug already exists.');
});
