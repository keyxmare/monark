<?php

declare(strict_types=1);

use App\Dependency\Application\Pipeline\Stage\PersistVersionsStage;
use App\Dependency\Application\Pipeline\SyncContext;
use App\Dependency\Domain\Model\DependencyVersion;
use App\Dependency\Domain\Repository\DependencyVersionRepositoryInterface;
use App\Shared\Domain\ValueObject\PackageManager;

function makePersistVersionRepo(): object
{
    return new class () implements DependencyVersionRepositoryInterface {
        /** @var list<DependencyVersion> */
        public array $saved = [];
        public bool $flushed = false;
        public bool $clearedLatest = false;

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
            return null;
        }

        public function save(DependencyVersion $version): void
        {
            $this->saved[] = $version;
        }

        public function flush(): void
        {
            $this->flushed = true;
        }

        public function clearLatestFlag(string $dependencyName, PackageManager $packageManager): void
        {
            $this->clearedLatest = true;
        }
    };
}

describe('PersistVersionsStage', function () {
    it('saves all new versions and flushes', function () {
        $repo = \makePersistVersionRepo();
        $stage = new PersistVersionsStage($repo);
        $v1 = DependencyVersion::create('vue', PackageManager::Npm, '1.0.0', isLatest: false);
        $v2 = DependencyVersion::create('vue', PackageManager::Npm, '2.0.0', isLatest: true);
        $ctx = SyncContext::initial('vue', PackageManager::Npm)
            ->withNewVersions([$v1, $v2]);

        $result = $stage($ctx);

        expect($repo->saved)->toHaveCount(2)
            ->and($repo->flushed)->toBeTrue()
            ->and($result->persistedVersions)->toHaveCount(2);
    });

    it('clears latest flag before persisting when new versions exist', function () {
        $repo = \makePersistVersionRepo();
        $stage = new PersistVersionsStage($repo);
        $v = DependencyVersion::create('vue', PackageManager::Npm, '2.0.0', isLatest: true);
        $ctx = SyncContext::initial('vue', PackageManager::Npm)
            ->withNewVersions([$v]);

        $stage($ctx);

        expect($repo->clearedLatest)->toBeTrue();
    });

    it('does nothing when newVersions is empty', function () {
        $repo = \makePersistVersionRepo();
        $stage = new PersistVersionsStage($repo);
        $ctx = SyncContext::initial('vue', PackageManager::Npm);

        $stage($ctx);

        expect($repo->saved)->toBeEmpty()
            ->and($repo->flushed)->toBeFalse()
            ->and($repo->clearedLatest)->toBeFalse();
    });
});
