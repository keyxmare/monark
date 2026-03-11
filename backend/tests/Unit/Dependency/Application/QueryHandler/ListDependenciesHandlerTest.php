<?php

declare(strict_types=1);

use App\Dependency\Application\DTO\DependencyListOutput;
use App\Dependency\Application\Query\ListDependenciesQuery;
use App\Dependency\Application\QueryHandler\ListDependenciesHandler;
use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\DependencyType;
use App\Dependency\Domain\Model\PackageManager;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubListDependenciesRepo(array $dependencies = [], int $count = 0): DependencyRepositoryInterface
{
    return new class ($dependencies, $count) implements DependencyRepositoryInterface {
        public function __construct(private readonly array $dependencies, private readonly int $count) {}
        public function findById(Uuid $id): ?Dependency { return null; }
        public function findAll(int $page = 1, int $perPage = 20): array { return $this->dependencies; }
        public function count(): int { return $this->count; }
        public function save(Dependency $dependency): void {}
        public function delete(Dependency $dependency): void {}
        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array { return []; }
        public function deleteByProjectId(Uuid $projectId): void {}
    };
}

describe('ListDependenciesHandler', function () {
    it('returns paginated dependencies', function () {
        $dep1 = Dependency::create(
            name: 'symfony/framework-bundle',
            currentVersion: '7.2.0',
            latestVersion: '8.0.0',
            ltsVersion: '7.4.0',
            packageManager: PackageManager::Composer,
            type: DependencyType::Runtime,
            isOutdated: true,
            project: Tests\Factory\Catalog\ProjectFactory::create(),
        );
        $dep2 = Dependency::create(
            name: 'vue',
            currentVersion: '3.4.0',
            latestVersion: '3.5.0',
            ltsVersion: '3.4.0',
            packageManager: PackageManager::Npm,
            type: DependencyType::Runtime,
            isOutdated: true,
            project: Tests\Factory\Catalog\ProjectFactory::create(),
        );

        $handler = new ListDependenciesHandler(stubListDependenciesRepo([$dep1, $dep2], 2));
        $result = $handler(new ListDependenciesQuery(1, 20));

        expect($result)->toBeInstanceOf(DependencyListOutput::class);
        expect($result->pagination->items)->toHaveCount(2);
        expect($result->pagination->total)->toBe(2);
    });

    it('returns empty list when no dependencies', function () {
        $handler = new ListDependenciesHandler(stubListDependenciesRepo([], 0));
        $result = $handler(new ListDependenciesQuery());

        expect($result->pagination->items)->toBeEmpty();
        expect($result->pagination->total)->toBe(0);
    });
});
