<?php

declare(strict_types=1);

use App\Dependency\Application\Command\UpdateDependencyCommand;
use App\Dependency\Application\CommandHandler\UpdateDependencyHandler;
use App\Dependency\Application\DTO\DependencyOutput;
use App\Dependency\Application\DTO\UpdateDependencyInput;
use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use Symfony\Component\Uid\Uuid;

function stubUpdateDependencyRepo(?Dependency $dependency = null): DependencyRepositoryInterface
{
    return new class ($dependency) implements DependencyRepositoryInterface {
        public ?Dependency $saved = null;
        public function __construct(private readonly ?Dependency $dependency)
        {
        }
        public function findById(Uuid $id): ?Dependency
        {
            return $this->dependency;
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

describe('UpdateDependencyHandler', function () {
    it('updates a dependency successfully', function () {
        $dependency = Dependency::create(
            name: 'symfony/framework-bundle',
            currentVersion: '7.2.0',
            latestVersion: '8.0.0',
            ltsVersion: '7.4.0',
            packageManager: PackageManager::Composer,
            type: DependencyType::Runtime,
            isOutdated: true,
            projectId: Uuid::v7(),
        );
        $dependencyId = $dependency->getId()->toRfc4122();

        $repo = \stubUpdateDependencyRepo($dependency);
        $handler = new UpdateDependencyHandler($repo, \Tests\Helpers\CacheHelper::createTagAwareCache());

        $input = new UpdateDependencyInput(currentVersion: '8.0.0', isOutdated: false);
        $result = $handler(new UpdateDependencyCommand($dependencyId, $input));

        expect($result)->toBeInstanceOf(DependencyOutput::class);
        expect($result->currentVersion)->toBe('8.0.0');
        expect($result->isOutdated)->toBeFalse();
        expect($repo->saved)->not->toBeNull();
    });

    it('updates repositoryUrl', function () {
        $dependency = Dependency::create(
            name: 'symfony/framework-bundle',
            currentVersion: '7.2.0',
            latestVersion: '8.0.0',
            ltsVersion: '7.4.0',
            packageManager: PackageManager::Composer,
            type: DependencyType::Runtime,
            isOutdated: true,
            projectId: Uuid::v7(),
        );
        $dependencyId = $dependency->getId()->toRfc4122();

        $repo = \stubUpdateDependencyRepo($dependency);
        $handler = new UpdateDependencyHandler($repo, \Tests\Helpers\CacheHelper::createTagAwareCache());

        $input = new UpdateDependencyInput(repositoryUrl: 'https://github.com/symfony/symfony');
        $result = $handler(new UpdateDependencyCommand($dependencyId, $input));

        expect($result->repositoryUrl)->toBe('https://github.com/symfony/symfony');
    });

    it('keeps repositoryUrl unchanged when not provided', function () {
        $dependency = Dependency::create(
            name: 'vue',
            currentVersion: '3.5.0',
            latestVersion: '3.5.0',
            ltsVersion: '3.5.0',
            packageManager: PackageManager::Npm,
            type: DependencyType::Runtime,
            isOutdated: false,
            projectId: Uuid::v7(),
            repositoryUrl: 'https://github.com/vuejs/core',
        );
        $dependencyId = $dependency->getId()->toRfc4122();

        $repo = \stubUpdateDependencyRepo($dependency);
        $handler = new UpdateDependencyHandler($repo, \Tests\Helpers\CacheHelper::createTagAwareCache());

        $input = new UpdateDependencyInput(currentVersion: '3.6.0');
        $result = $handler(new UpdateDependencyCommand($dependencyId, $input));

        expect($result->currentVersion)->toBe('3.6.0');
        expect($result->repositoryUrl)->toBe('https://github.com/vuejs/core');
    });

    it('throws not found when dependency does not exist', function () {
        $handler = new UpdateDependencyHandler(\stubUpdateDependencyRepo(null), \Tests\Helpers\CacheHelper::createTagAwareCache());
        $input = new UpdateDependencyInput(name: 'new-name');
        $handler(new UpdateDependencyCommand('00000000-0000-0000-0000-000000000000', $input));
    })->throws(NotFoundException::class);
});
