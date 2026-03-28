<?php

declare(strict_types=1);

use App\Dependency\Application\Command\DeleteDependencyCommand;
use App\Dependency\Application\CommandHandler\DeleteDependencyHandler;
use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use Symfony\Component\Uid\Uuid;

function stubDeleteDependencyRepo(?Dependency $dependency = null): DependencyRepositoryInterface
{
    return new class ($dependency) implements DependencyRepositoryInterface {
        public bool $deleted = false;
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
        }
        public function delete(Dependency $dependency): void
        {
            $this->deleted = true;
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

describe('DeleteDependencyHandler', function () {
    it('deletes a dependency successfully', function () {
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
        $repo = \stubDeleteDependencyRepo($dependency);
        $handler = new DeleteDependencyHandler($repo);

        $handler(new DeleteDependencyCommand($dependency->getId()->toRfc4122()));

        expect($repo->deleted)->toBeTrue();
    });

    it('throws not found when dependency does not exist', function () {
        $handler = new DeleteDependencyHandler(\stubDeleteDependencyRepo(null));
        $handler(new DeleteDependencyCommand('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});
