<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Dependency\Application\Command\CreateDependencyCommand;
use App\Dependency\Application\CommandHandler\CreateDependencyHandler;
use App\Dependency\Application\DTO\CreateDependencyInput;
use App\Dependency\Application\DTO\DependencyOutput;
use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\ProjectFactory;

function stubCreateDependencyRepo(): DependencyRepositoryInterface
{
    return new class () implements DependencyRepositoryInterface {
        public ?Dependency $saved = null;
        public function findById(Uuid $id): ?Dependency
        {
            return null;
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function count(): int
        {
            return 0;
        }
        public function save(Dependency $dependency): void
        {
            $this->saved = $dependency;
        }
        public function delete(Dependency $dependency): void
        {
        }
        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function countByProjectId(Uuid $projectId): int
        {
            return 0;
        }
        public function deleteByProjectId(Uuid $projectId): void
        {
        }
    };
}

function stubCreateDependencyProjectRepo(?Project $project = null): ProjectRepositoryInterface
{
    return new class ($project) implements ProjectRepositoryInterface {
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
        }
        public function delete(Project $project): void
        {
        }
    };
}

describe('CreateDependencyHandler', function () {
    it('creates a dependency successfully', function () {
        $project = ProjectFactory::create();
        $repo = \stubCreateDependencyRepo();
        $projectRepo = \stubCreateDependencyProjectRepo($project);
        $handler = new CreateDependencyHandler($repo, $projectRepo);

        $input = new CreateDependencyInput(
            name: 'symfony/framework-bundle',
            currentVersion: '7.2.0',
            latestVersion: '8.0.0',
            ltsVersion: '7.4.0',
            packageManager: 'composer',
            type: 'runtime',
            isOutdated: true,
            projectId: $project->getId()->toRfc4122(),
        );

        $result = $handler(new CreateDependencyCommand($input));

        expect($result)->toBeInstanceOf(DependencyOutput::class);
        expect($result->name)->toBe('symfony/framework-bundle');
        expect($result->currentVersion)->toBe('7.2.0');
        expect($result->latestVersion)->toBe('8.0.0');
        expect($result->packageManager)->toBe('composer');
        expect($result->type)->toBe('runtime');
        expect($result->isOutdated)->toBeTrue();
        expect($result->projectId)->toBe($project->getId()->toRfc4122());
        expect($repo->saved)->not->toBeNull();
    });

    it('throws not found when project does not exist', function () {
        $repo = \stubCreateDependencyRepo();
        $projectRepo = \stubCreateDependencyProjectRepo(null);
        $handler = new CreateDependencyHandler($repo, $projectRepo);

        $input = new CreateDependencyInput(
            name: 'symfony/framework-bundle',
            currentVersion: '7.2.0',
            latestVersion: '8.0.0',
            ltsVersion: '7.4.0',
            packageManager: 'composer',
            type: 'runtime',
            isOutdated: true,
            projectId: '00000000-0000-0000-0000-000000000000',
        );

        $handler(new CreateDependencyCommand($input));
    })->throws(NotFoundException::class);

    it('returns correct projectId in output', function () {
        $project = ProjectFactory::create();
        $repo = \stubCreateDependencyRepo();
        $projectRepo = \stubCreateDependencyProjectRepo($project);
        $handler = new CreateDependencyHandler($repo, $projectRepo);

        $input = new CreateDependencyInput(
            name: 'vue',
            currentVersion: '3.5.0',
            latestVersion: '3.5.0',
            ltsVersion: '3.5.0',
            packageManager: 'npm',
            type: 'runtime',
            isOutdated: false,
            projectId: $project->getId()->toRfc4122(),
        );

        $result = $handler(new CreateDependencyCommand($input));

        expect($result->projectId)->toBe($project->getId()->toRfc4122());
    });

    it('creates a dependency with repositoryUrl', function () {
        $project = ProjectFactory::create();
        $repo = \stubCreateDependencyRepo();
        $projectRepo = \stubCreateDependencyProjectRepo($project);
        $handler = new CreateDependencyHandler($repo, $projectRepo);

        $input = new CreateDependencyInput(
            name: 'symfony/framework-bundle',
            currentVersion: '7.2.0',
            latestVersion: '8.0.0',
            ltsVersion: '7.4.0',
            packageManager: 'composer',
            type: 'runtime',
            isOutdated: true,
            projectId: $project->getId()->toRfc4122(),
            repositoryUrl: 'https://github.com/symfony/symfony',
        );

        $result = $handler(new CreateDependencyCommand($input));

        expect($result->repositoryUrl)->toBe('https://github.com/symfony/symfony');
    });

    it('creates a dependency without repositoryUrl', function () {
        $project = ProjectFactory::create();
        $repo = \stubCreateDependencyRepo();
        $projectRepo = \stubCreateDependencyProjectRepo($project);
        $handler = new CreateDependencyHandler($repo, $projectRepo);

        $input = new CreateDependencyInput(
            name: 'vue',
            currentVersion: '3.5.0',
            latestVersion: '3.5.0',
            ltsVersion: '3.5.0',
            packageManager: 'npm',
            type: 'runtime',
            isOutdated: false,
            projectId: $project->getId()->toRfc4122(),
        );

        $result = $handler(new CreateDependencyCommand($input));

        expect($result->repositoryUrl)->toBeNull();
    });
});
