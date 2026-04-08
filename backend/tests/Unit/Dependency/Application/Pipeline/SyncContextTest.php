<?php

declare(strict_types=1);

use App\Dependency\Application\Pipeline\SyncContext;
use App\Dependency\Domain\DTO\RegistryVersion;
use App\Dependency\Domain\Model\DependencyVersion;
use App\Shared\Domain\ValueObject\PackageManager;

function makeRegistryVersion(string $version, bool $isLatest = false): RegistryVersion
{
    return new RegistryVersion($version, new \DateTimeImmutable('2024-01-01'), $isLatest);
}

function makeDepVersion(string $version): DependencyVersion
{
    return DependencyVersion::create(
        dependencyName: 'test-pkg',
        packageManager: PackageManager::Npm,
        version: $version,
        isLatest: false,
    );
}

describe('SyncContext', function () {
    it('starts with initial values', function () {
        $ctx = SyncContext::initial(packageName: 'vue', packageManager: PackageManager::Npm);

        expect($ctx->packageName)->toBe('vue')
            ->and($ctx->packageManager)->toBe(PackageManager::Npm)
            ->and($ctx->registryVersions)->toBeEmpty()
            ->and($ctx->newVersions)->toBeEmpty()
            ->and($ctx->persistedVersions)->toBeEmpty()
            ->and($ctx->latestVersion)->toBeNull()
            ->and($ctx->syncId)->toBeNull()
            ->and($ctx->index)->toBe(0)
            ->and($ctx->total)->toBe(0);
    });

    it('withRegistryVersions returns new instance with versions', function () {
        $ctx = SyncContext::initial('vue', PackageManager::Npm);
        $versions = [\makeRegistryVersion('1.0.0', true)];

        $next = $ctx->withRegistryVersions($versions);

        expect($next->registryVersions)->toHaveCount(1)
            ->and($ctx->registryVersions)->toBeEmpty();
    });

    it('withNewVersions returns new instance preserving existing state', function () {
        $rv = \makeRegistryVersion('2.0.0', true);
        $ctx = SyncContext::initial('vue', PackageManager::Npm)
            ->withRegistryVersions([$rv]);

        $dv = \makeDepVersion('2.0.0');
        $next = $ctx->withNewVersions([$dv]);

        expect($next->newVersions)->toHaveCount(1)
            ->and($next->registryVersions)->toHaveCount(1);
    });

    it('withPersistedVersions captures saved versions', function () {
        $dv = \makeDepVersion('3.0.0');
        $ctx = SyncContext::initial('pkg', PackageManager::Composer)
            ->withPersistedVersions([$dv]);

        expect($ctx->persistedVersions)->toHaveCount(1);
    });

    it('withLatestVersion sets the resolved latest string', function () {
        $ctx = SyncContext::initial('pkg', PackageManager::Npm)
            ->withLatestVersion('4.2.1');

        expect($ctx->latestVersion)->toBe('4.2.1');
    });

    it('withProgress stores syncId index and total', function () {
        $ctx = SyncContext::initial('pkg', PackageManager::Npm)
            ->withProgress(syncId: 'abc-123', index: 3, total: 10);

        expect($ctx->syncId)->toBe('abc-123')
            ->and($ctx->index)->toBe(3)
            ->and($ctx->total)->toBe(10);
    });

    it('is immutable — original context unchanged after with* calls', function () {
        $original = SyncContext::initial('vue', PackageManager::Npm);
        $original->withRegistryVersions([\makeRegistryVersion('1.0.0')]);
        $original->withLatestVersion('1.0.0');

        expect($original->registryVersions)->toBeEmpty()
            ->and($original->latestVersion)->toBeNull();
    });
});
