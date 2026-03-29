<?php

declare(strict_types=1);

use App\Dependency\Application\EventListener\UpdateDependencyVersionStatusListener;
use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\RegistryStatus;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\Event\ProductVersionsSyncedEvent;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use Symfony\Component\Uid\Uuid;

function makeComposerDependency(string $name, string $currentVersion): Dependency
{
    return Dependency::create(
        name: $name,
        currentVersion: $currentVersion,
        latestVersion: $currentVersion,
        ltsVersion: '',
        packageManager: PackageManager::Composer,
        type: DependencyType::Runtime,
        isOutdated: false,
        projectId: Uuid::v7(),
    );
}

function makeNpmDependency(string $name, string $currentVersion): Dependency
{
    return Dependency::create(
        name: $name,
        currentVersion: $currentVersion,
        latestVersion: $currentVersion,
        ltsVersion: '',
        packageManager: PackageManager::Npm,
        type: DependencyType::Runtime,
        isOutdated: false,
        projectId: Uuid::v7(),
    );
}

describe('UpdateDependencyVersionStatusListener', function () {
    it('skips events without a packageManager', function () {
        $repo = $this->createMock(DependencyRepositoryInterface::class);
        $repo->expects($this->never())->method('findByName');
        $repo->expects($this->never())->method('save');

        $event = new ProductVersionsSyncedEvent(
            productName: 'symfony',
            packageManager: null,
            latestVersion: '7.2.0',
            ltsVersion: null,
        );

        $listener = new UpdateDependencyVersionStatusListener($repo);
        $listener($event);
    });

    it('skips events without a latestVersion', function () {
        $repo = $this->createMock(DependencyRepositoryInterface::class);
        $repo->expects($this->never())->method('findByName');
        $repo->expects($this->never())->method('save');

        $event = new ProductVersionsSyncedEvent(
            productName: 'symfony/symfony',
            packageManager: PackageManager::Composer,
            latestVersion: null,
            ltsVersion: null,
        );

        $listener = new UpdateDependencyVersionStatusListener($repo);
        $listener($event);
    });

    it('updates outdated dependency with new latest version and marks registry synced', function () {
        $dep = makeComposerDependency('symfony/symfony', '6.4.0');

        $repo = $this->createMock(DependencyRepositoryInterface::class);
        $repo->method('findByName')->with('symfony/symfony', 'composer')->willReturn([$dep]);
        $repo->expects($this->once())->method('save')->with($dep);

        $event = new ProductVersionsSyncedEvent(
            productName: 'symfony/symfony',
            packageManager: PackageManager::Composer,
            latestVersion: '7.2.0',
            ltsVersion: '7.1.0',
        );

        $listener = new UpdateDependencyVersionStatusListener($repo);
        $listener($event);

        expect($dep->getLatestVersion())->toBe('7.2.0')
            ->and($dep->getLtsVersion())->toBe('7.1.0')
            ->and($dep->isOutdated())->toBeTrue()
            ->and($dep->getRegistryStatus())->toBe(RegistryStatus::Synced);
    });

    it('marks dependency as not outdated when current version matches latest', function () {
        $dep = makeComposerDependency('symfony/symfony', '7.2.0');

        $repo = $this->createMock(DependencyRepositoryInterface::class);
        $repo->method('findByName')->with('symfony/symfony', 'composer')->willReturn([$dep]);
        $repo->expects($this->once())->method('save')->with($dep);

        $event = new ProductVersionsSyncedEvent(
            productName: 'symfony/symfony',
            packageManager: PackageManager::Composer,
            latestVersion: '7.2.0',
            ltsVersion: null,
        );

        $listener = new UpdateDependencyVersionStatusListener($repo);
        $listener($event);

        expect($dep->isOutdated())->toBeFalse()
            ->and($dep->getRegistryStatus())->toBe(RegistryStatus::Synced);
    });

    it('updates multiple dependencies for the same package', function () {
        $dep1 = makeNpmDependency('vue', '3.3.0');
        $dep2 = makeNpmDependency('vue', '3.4.0');

        $savedDeps = [];
        $repo = $this->createMock(DependencyRepositoryInterface::class);
        $repo->method('findByName')->with('vue', 'npm')->willReturn([$dep1, $dep2]);
        $repo->expects($this->exactly(2))->method('save')->willReturnCallback(
            function (Dependency $d) use (&$savedDeps): void {
                $savedDeps[] = $d;
            }
        );

        $event = new ProductVersionsSyncedEvent(
            productName: 'vue',
            packageManager: PackageManager::Npm,
            latestVersion: '3.5.0',
            ltsVersion: null,
        );

        $listener = new UpdateDependencyVersionStatusListener($repo);
        $listener($event);

        expect($dep1->isOutdated())->toBeTrue()
            ->and($dep2->isOutdated())->toBeTrue()
            ->and($dep1->getRegistryStatus())->toBe(RegistryStatus::Synced)
            ->and($dep2->getRegistryStatus())->toBe(RegistryStatus::Synced);
    });

    it('does nothing when no dependencies are found for the package', function () {
        $repo = $this->createMock(DependencyRepositoryInterface::class);
        $repo->method('findByName')->with('unknown/package', 'composer')->willReturn([]);
        $repo->expects($this->never())->method('save');

        $event = new ProductVersionsSyncedEvent(
            productName: 'unknown/package',
            packageManager: PackageManager::Composer,
            latestVersion: '1.0.0',
            ltsVersion: null,
        );

        $listener = new UpdateDependencyVersionStatusListener($repo);
        $listener($event);
    });

    it('updates ltsVersion to empty string when event ltsVersion is null', function () {
        $dep = makeComposerDependency('laravel/framework', '10.0.0');

        $repo = $this->createMock(DependencyRepositoryInterface::class);
        $repo->method('findByName')->willReturn([$dep]);
        $repo->expects($this->once())->method('save');

        $event = new ProductVersionsSyncedEvent(
            productName: 'laravel/framework',
            packageManager: PackageManager::Composer,
            latestVersion: '11.0.0',
            ltsVersion: null,
        );

        $listener = new UpdateDependencyVersionStatusListener($repo);
        $listener($event);

        // ltsVersion stays unchanged (null ltsVersion in event means we don't pass ltsVersion)
        expect($dep->getLatestVersion())->toBe('11.0.0')
            ->and($dep->getRegistryStatus())->toBe(RegistryStatus::Synced);
    });
});
