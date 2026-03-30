<?php

declare(strict_types=1);

use App\Dependency\Application\Pipeline\Stage\FilterNewVersionsStage;
use App\Dependency\Application\Pipeline\SyncContext;
use App\Dependency\Domain\DTO\RegistryVersion;
use App\Dependency\Domain\Model\DependencyVersion;
use App\Dependency\Domain\Repository\DependencyVersionRepositoryInterface;
use App\Shared\Domain\ValueObject\PackageManager;

function makeFilterVersionRepo(?DependencyVersion $existing = null): DependencyVersionRepositoryInterface
{
    return new class ($existing) implements DependencyVersionRepositoryInterface {
        public function __construct(private readonly ?DependencyVersion $existing)
        {
        }

        public function findByNameAndManager(string $dependencyName, PackageManager $packageManager): array
        {
            return [];
        }

        public function findLatestByNameAndManager(string $dependencyName, PackageManager $packageManager): ?DependencyVersion
        {
            return null;
        }

        public function findByNameManagerAndVersion(string $dependencyName, PackageManager $packageManager, string $version): ?DependencyVersion
        {
            return $this->existing;
        }

        public function save(DependencyVersion $version): void
        {
        }

        public function flush(): void
        {
        }

        public function clearLatestFlag(string $dependencyName, PackageManager $packageManager): void
        {
        }
    };
}

describe('FilterNewVersionsStage', function () {
    it('keeps versions not already in repository', function () {
        $repo = \makeFilterVersionRepo(existing: null);
        $stage = new FilterNewVersionsStage($repo);
        $ctx = SyncContext::initial('vue', PackageManager::Npm)
            ->withRegistryVersions([
                new RegistryVersion('1.0.0', new \DateTimeImmutable(), false),
                new RegistryVersion('1.1.0', new \DateTimeImmutable(), true),
            ]);

        $result = $stage($ctx);

        expect($result->newVersions)->toHaveCount(2);
    });

    it('filters out versions already in repository', function () {
        $existing = DependencyVersion::create('vue', PackageManager::Npm, '1.0.0', isLatest: false);
        $repo = \makeFilterVersionRepo(existing: $existing);
        $stage = new FilterNewVersionsStage($repo);
        $ctx = SyncContext::initial('vue', PackageManager::Npm)
            ->withRegistryVersions([
                new RegistryVersion('1.0.0', new \DateTimeImmutable(), false),
            ]);

        $result = $stage($ctx);

        expect($result->newVersions)->toBeEmpty();
    });

    it('extracts latestVersion from registry versions marked isLatest', function () {
        $repo = \makeFilterVersionRepo(existing: null);
        $stage = new FilterNewVersionsStage($repo);
        $ctx = SyncContext::initial('vue', PackageManager::Npm)
            ->withRegistryVersions([
                new RegistryVersion('1.0.0', new \DateTimeImmutable(), false),
                new RegistryVersion('2.0.0', new \DateTimeImmutable(), true),
            ]);

        $result = $stage($ctx);

        expect($result->latestVersion)->toBe('2.0.0');
    });

    it('latestVersion remains null when no version is marked isLatest', function () {
        $repo = \makeFilterVersionRepo(existing: null);
        $stage = new FilterNewVersionsStage($repo);
        $ctx = SyncContext::initial('vue', PackageManager::Npm)
            ->withRegistryVersions([
                new RegistryVersion('1.0.0', new \DateTimeImmutable(), false),
            ]);

        $result = $stage($ctx);

        expect($result->latestVersion)->toBeNull();
    });

    it('returns context unchanged when registryVersions is empty', function () {
        $repo = \makeFilterVersionRepo();
        $stage = new FilterNewVersionsStage($repo);
        $ctx = SyncContext::initial('vue', PackageManager::Npm);

        $result = $stage($ctx);

        expect($result->newVersions)->toBeEmpty()
            ->and($result->latestVersion)->toBeNull();
    });
});
