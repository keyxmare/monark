<?php

declare(strict_types=1);

use App\Hub\Shared\Domain\Exception\NotFoundException;
use App\Monitoring\Catalog\Application\Command\UpdateProjectCommand;
use App\Monitoring\Catalog\Application\CommandHandler\UpdateProjectHandler;
use App\Monitoring\Catalog\Application\DTO\ProjectOutput;
use App\Monitoring\Catalog\Application\DTO\UpdateProjectInput;
use App\Monitoring\Catalog\Domain\Model\Project;
use App\Monitoring\Catalog\Domain\Model\ProjectVisibility;
use App\Monitoring\Catalog\Domain\Repository\ProjectRepositoryInterface;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Monitoring\Catalog\ProjectFactory;

function stubUpdateProjectRepo(?Project $project = null): ProjectRepositoryInterface
{
    return new class ($project) implements ProjectRepositoryInterface {
        public ?Project $saved = null;
        public function __construct(private readonly ?Project $project)
        {
        }
        public function findById(Uuid $id): ?Project
        {
            return $this->project;
        }
        public function findBySlug(string $slug): ?Project
        {
            return null;
        }
        public function findByExternalIdAndProvider(string $externalId, Uuid $providerId): ?Project
        {
            return null;
        }
        public function findExternalIdMapByProvider(Uuid $providerId): array
        {
            return [];
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function findByProviderId(Uuid $providerId): array
        {
            return [];
        }
        public function findAllWithProvider(): array
        {
            return [];
        }
        public function count(): int
        {
            return 0;
        }
        public function save(Project $project): void
        {
            $this->saved = $project;
        }
        public function delete(Project $project): void
        {
        }
    };
}

describe('UpdateProjectHandler', function () {
    it('updates a project successfully', function () {
        $project = ProjectFactory::create();
        $repo = \stubUpdateProjectRepo($project);
        $handler = new UpdateProjectHandler($repo, \Tests\Helpers\CacheHelper::createTagAwareCache());

        $input = new UpdateProjectInput(
            name: 'Updated Name',
            visibility: ProjectVisibility::Public,
        );

        $result = $handler(new UpdateProjectCommand($project->getId()->toRfc4122(), $input));

        expect($result)->toBeInstanceOf(ProjectOutput::class);
        expect($result->name)->toBe('Updated Name');
        expect($result->visibility)->toBe('public');
        expect($repo->saved)->not->toBeNull();
    });

    it('throws not found when project does not exist', function () {
        $handler = new UpdateProjectHandler(\stubUpdateProjectRepo(null), \Tests\Helpers\CacheHelper::createTagAwareCache());
        $input = new UpdateProjectInput(name: 'Updated');
        $handler(new UpdateProjectCommand('00000000-0000-0000-0000-000000000000', $input));
    })->throws(NotFoundException::class);
});
