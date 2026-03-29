<?php

declare(strict_types=1);

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Dependency\Infrastructure\Adapter\DependencyWriterAdapter;
use Symfony\Component\Uid\Uuid;

function stubDepWriterRepo(): DependencyRepositoryInterface&stdClass
{
    return new class () extends stdClass implements DependencyRepositoryInterface {
        /** @var list<Dependency> */
        public array $saved = [];
        public bool $deletedByProjectId = false;
        public ?Uuid $deletedProjectId = null;

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
            $this->saved[] = $dependency;
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
            $this->deletedByProjectId = true;
            $this->deletedProjectId = $projectId;
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

describe('DependencyWriterAdapter', function () {
    it('creates dependency from scan data', function () {
        $repo = \stubDepWriterRepo();
        $adapter = new DependencyWriterAdapter($repo);
        $projectId = Uuid::v7();

        $adapter->createFromScan(
            name: 'vue',
            currentVersion: '3.5.0',
            packageManager: 'npm',
            type: 'runtime',
            projectId: $projectId,
            repositoryUrl: 'https://github.com/vuejs/core',
        );

        expect($repo->saved)->toHaveCount(1);
        expect($repo->saved[0])->toBeInstanceOf(Dependency::class);
        expect($repo->saved[0]->getName())->toBe('vue');
        expect($repo->saved[0]->getCurrentVersion())->toBe('3.5.0');
        expect($repo->saved[0]->getProjectId())->toEqual($projectId);
    });

    it('calls deleteByProjectId on repository', function () {
        $repo = \stubDepWriterRepo();
        $adapter = new DependencyWriterAdapter($repo);
        $projectId = Uuid::v7();

        $adapter->deleteByProjectId($projectId);

        expect($repo->deletedByProjectId)->toBeTrue();
        expect($repo->deletedProjectId)->toEqual($projectId);
    });
});
