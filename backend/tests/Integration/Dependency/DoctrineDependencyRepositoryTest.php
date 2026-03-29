<?php

declare(strict_types=1);

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\Severity;
use App\Dependency\Domain\Model\Vulnerability;
use App\Dependency\Domain\Model\VulnerabilityStatus;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use App\Tests\Helpers\DatabaseHelper;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(DependencyRepositoryInterface::class);
});

function createDependency(
    string $name = 'symfony/console',
    PackageManager $pm = PackageManager::Composer,
    DependencyType $type = DependencyType::Runtime,
    bool $isOutdated = false,
    ?Uuid $projectId = null,
    ?string $repositoryUrl = null,
): Dependency {
    return Dependency::create(
        name: $name,
        currentVersion: '6.0.0',
        latestVersion: '7.0.0',
        ltsVersion: '6.4.0',
        packageManager: $pm,
        type: $type,
        isOutdated: $isOutdated,
        projectId: $projectId ?? Uuid::v7(),
        repositoryUrl: $repositoryUrl,
    );
}

describe('DoctrineDependencyRepository', function () {
    it('saves and finds a dependency by id', function () {
        $dep = createDependency();
        $this->repo->save($dep);

        $found = $this->repo->findById($dep->getId());

        expect($found)->not->toBeNull();
        expect($found->getName())->toBe('symfony/console');
        expect($found->getPackageManager())->toBe(PackageManager::Composer);
    });

    it('returns null for unknown id', function () {
        expect($this->repo->findById(Uuid::v7()))->toBeNull();
    });

    it('finds by project id with pagination', function () {
        $projectId = Uuid::v7();

        for ($i = 0; $i < 5; $i++) {
            $this->repo->save(createDependency(name: "pkg/{$i}", projectId: $projectId));
        }

        $page1 = $this->repo->findByProjectId($projectId, page: 1, perPage: 3);
        expect($page1)->toHaveCount(3);

        $page2 = $this->repo->findByProjectId($projectId, page: 2, perPage: 3);
        expect($page2)->toHaveCount(2);
    });

    it('counts by project id', function () {
        $projectId = Uuid::v7();
        expect($this->repo->countByProjectId($projectId))->toBe(0);

        $this->repo->save(createDependency(projectId: $projectId));
        $this->repo->save(createDependency(name: 'other/pkg', projectId: $projectId));

        expect($this->repo->countByProjectId($projectId))->toBe(2);
    });

    it('deletes by project id (bulk)', function () {
        $projectId = Uuid::v7();

        $this->repo->save(createDependency(name: 'a/a', projectId: $projectId));
        $this->repo->save(createDependency(name: 'b/b', projectId: $projectId));
        $this->repo->save(createDependency(name: 'c/c'));

        $this->repo->deleteByProjectId($projectId);
        $this->getEntityManager()->clear();

        expect($this->repo->countByProjectId($projectId))->toBe(0);
        expect($this->repo->count())->toBe(1);
    });

    it('filters by packageManager', function () {
        $projectId = Uuid::v7();
        $this->repo->save(createDependency(name: 'a', pm: PackageManager::Composer, projectId: $projectId));
        $this->repo->save(createDependency(name: 'b', pm: PackageManager::Npm, projectId: $projectId));

        $filtered = $this->repo->findFiltered(1, 20, [
            'projectId' => $projectId->toRfc4122(),
            'packageManager' => PackageManager::Composer->value,
        ]);

        expect($filtered)->toHaveCount(1);
        expect($filtered[0]->getName())->toBe('a');
    });

    it('filters by isOutdated', function () {
        $projectId = Uuid::v7();
        $this->repo->save(createDependency(name: 'outdated', isOutdated: true, projectId: $projectId));
        $this->repo->save(createDependency(name: 'current', isOutdated: false, projectId: $projectId));

        $filtered = $this->repo->findFiltered(1, 20, [
            'projectId' => $projectId->toRfc4122(),
            'isOutdated' => true,
        ]);

        expect($filtered)->toHaveCount(1);
        expect($filtered[0]->getName())->toBe('outdated');
    });

    it('filters by search term', function () {
        $projectId = Uuid::v7();
        $this->repo->save(createDependency(name: 'symfony/console', projectId: $projectId));
        $this->repo->save(createDependency(name: 'laravel/framework', projectId: $projectId));

        $filtered = $this->repo->findFiltered(1, 20, [
            'projectId' => $projectId->toRfc4122(),
            'search' => 'symfony',
        ]);

        expect($filtered)->toHaveCount(1);
        expect($filtered[0]->getName())->toBe('symfony/console');
    });

    it('counts filtered results', function () {
        $projectId = Uuid::v7();
        $this->repo->save(createDependency(name: 'a', pm: PackageManager::Composer, projectId: $projectId));
        $this->repo->save(createDependency(name: 'b', pm: PackageManager::Npm, projectId: $projectId));
        $this->repo->save(createDependency(name: 'c', pm: PackageManager::Composer, projectId: $projectId));

        $count = $this->repo->countFiltered([
            'projectId' => $projectId->toRfc4122(),
            'packageManager' => PackageManager::Composer->value,
        ]);

        expect($count)->toBe(2);
    });

    it('finds unique packages', function () {
        $p1 = Uuid::v7();
        $p2 = Uuid::v7();

        $this->repo->save(createDependency(name: 'symfony/console', pm: PackageManager::Composer, projectId: $p1));
        $this->repo->save(createDependency(name: 'symfony/console', pm: PackageManager::Composer, projectId: $p2));
        $this->repo->save(createDependency(name: 'react', pm: PackageManager::Npm, projectId: $p1));

        $packages = $this->repo->findUniquePackages();

        expect($packages)->toHaveCount(2);
    });

    it('finds by name and package manager', function () {
        $p1 = Uuid::v7();
        $p2 = Uuid::v7();

        $this->repo->save(createDependency(name: 'symfony/console', pm: PackageManager::Composer, projectId: $p1));
        $this->repo->save(createDependency(name: 'symfony/console', pm: PackageManager::Composer, projectId: $p2));
        $this->repo->save(createDependency(name: 'react', pm: PackageManager::Npm, projectId: $p1));

        $found = $this->repo->findByName('symfony/console', PackageManager::Composer->value);

        expect($found)->toHaveCount(2);
    });

    it('gets stats with total, outdated, and vulnerability counts', function () {
        $projectId = Uuid::v7();

        $dep1 = createDependency(name: 'a', isOutdated: true, projectId: $projectId);
        $this->repo->save($dep1);

        $dep2 = createDependency(name: 'b', isOutdated: false, projectId: $projectId);
        $this->repo->save($dep2);

        // Add a vulnerability to dep1 via the entity manager (cascade persist)
        $vuln = Vulnerability::create(
            cveId: 'CVE-2024-0001',
            severity: Severity::High,
            title: 'Test Vuln',
            description: 'A test vulnerability',
            patchedVersion: '7.0.1',
            status: VulnerabilityStatus::Open,
            detectedAt: new DateTimeImmutable(),
            dependency: $dep1,
        );
        $this->getEntityManager()->persist($vuln);
        $this->getEntityManager()->flush();

        $stats = $this->repo->getStats(['projectId' => $projectId->toRfc4122()]);

        expect($stats['total'])->toBe(2);
        expect($stats['outdated'])->toBe(1);
        expect($stats['totalVulnerabilities'])->toBe(1);
    });

    it('deletes a single dependency', function () {
        $dep = createDependency();
        $this->repo->save($dep);
        $id = $dep->getId();

        $this->repo->delete($dep);
        $this->getEntityManager()->clear();

        expect($this->repo->findById($id))->toBeNull();
    });
});
