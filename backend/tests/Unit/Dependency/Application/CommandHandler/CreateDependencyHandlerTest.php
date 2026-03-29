<?php

declare(strict_types=1);

use App\Dependency\Application\Command\CreateDependencyCommand;
use App\Dependency\Application\CommandHandler\CreateDependencyHandler;
use App\Dependency\Application\DTO\CreateDependencyInput;
use App\Dependency\Application\DTO\DependencyOutput;
use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use Symfony\Component\Uid\Uuid;

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

        public function findFiltered(int $page, int $perPage, array $filters = []): array
        {
            return [];
        }

        public function countFiltered(array $filters = []): int
        {
            return 0;
        }

        public function findUniquePackages(): array
        {
            return [];
        }

        public function findByName(string $name, string $packageManager): array
        {
            return [];
        }

        public function getStats(array $filters = []): array
        {
            return ['total' => 0, 'outdated' => 0, 'totalVulnerabilities' => 0];
        }
    };
}

describe('CreateDependencyHandler', function () {
    it('creates a dependency successfully', function () {
        $projectId = Uuid::v7();
        $repo = \stubCreateDependencyRepo();
        $handler = new CreateDependencyHandler($repo, \Tests\Helpers\CacheHelper::createTagAwareCache());

        $input = new CreateDependencyInput(
            name: 'symfony/framework-bundle',
            currentVersion: '7.2.0',
            latestVersion: '8.0.0',
            ltsVersion: '7.4.0',
            packageManager: 'composer',
            type: 'runtime',
            isOutdated: true,
            projectId: $projectId->toRfc4122(),
        );

        $result = $handler(new CreateDependencyCommand($input));

        expect($result)->toBeInstanceOf(DependencyOutput::class);
        expect($result->name)->toBe('symfony/framework-bundle');
        expect($result->currentVersion)->toBe('7.2.0');
        expect($result->latestVersion)->toBe('8.0.0');
        expect($result->packageManager)->toBe('composer');
        expect($result->type)->toBe('runtime');
        expect($result->isOutdated)->toBeTrue();
        expect($result->projectId)->toBe($projectId->toRfc4122());
        expect($repo->saved)->not->toBeNull();
    });

    it('returns correct projectId in output', function () {
        $projectId = Uuid::v7();
        $repo = \stubCreateDependencyRepo();
        $handler = new CreateDependencyHandler($repo, \Tests\Helpers\CacheHelper::createTagAwareCache());

        $input = new CreateDependencyInput(
            name: 'vue',
            currentVersion: '3.5.0',
            latestVersion: '3.5.0',
            ltsVersion: '3.5.0',
            packageManager: 'npm',
            type: 'runtime',
            isOutdated: false,
            projectId: $projectId->toRfc4122(),
        );

        $result = $handler(new CreateDependencyCommand($input));

        expect($result->projectId)->toBe($projectId->toRfc4122());
    });

    it('creates a dependency with repositoryUrl', function () {
        $projectId = Uuid::v7();
        $repo = \stubCreateDependencyRepo();
        $handler = new CreateDependencyHandler($repo, \Tests\Helpers\CacheHelper::createTagAwareCache());

        $input = new CreateDependencyInput(
            name: 'symfony/framework-bundle',
            currentVersion: '7.2.0',
            latestVersion: '8.0.0',
            ltsVersion: '7.4.0',
            packageManager: 'composer',
            type: 'runtime',
            isOutdated: true,
            projectId: $projectId->toRfc4122(),
            repositoryUrl: 'https://github.com/symfony/symfony',
        );

        $result = $handler(new CreateDependencyCommand($input));

        expect($result->repositoryUrl)->toBe('https://github.com/symfony/symfony');
    });

    it('creates a dependency without repositoryUrl', function () {
        $projectId = Uuid::v7();
        $repo = \stubCreateDependencyRepo();
        $handler = new CreateDependencyHandler($repo, \Tests\Helpers\CacheHelper::createTagAwareCache());

        $input = new CreateDependencyInput(
            name: 'vue',
            currentVersion: '3.5.0',
            latestVersion: '3.5.0',
            ltsVersion: '3.5.0',
            packageManager: 'npm',
            type: 'runtime',
            isOutdated: false,
            projectId: $projectId->toRfc4122(),
        );

        $result = $handler(new CreateDependencyCommand($input));

        expect($result->repositoryUrl)->toBeNull();
    });
});
