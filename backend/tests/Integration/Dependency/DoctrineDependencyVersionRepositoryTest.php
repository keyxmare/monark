<?php

declare(strict_types=1);

use App\Dependency\Domain\Model\DependencyVersion;
use App\Dependency\Domain\Repository\DependencyVersionRepositoryInterface;
use App\Shared\Domain\ValueObject\PackageManager;
use App\Tests\Helpers\DatabaseHelper;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(DependencyVersionRepositoryInterface::class);
});

describe('DoctrineDependencyVersionRepository', function () {
    it('saves and finds by name and manager', function () {
        $v1 = DependencyVersion::create('symfony/console', PackageManager::Composer, '6.0.0');
        $v2 = DependencyVersion::create('symfony/console', PackageManager::Composer, '7.0.0');
        $v3 = DependencyVersion::create('react', PackageManager::Npm, '18.0.0');

        $this->repo->save($v1);
        $this->repo->save($v2);
        $this->repo->save($v3);

        $found = $this->repo->findByNameAndManager('symfony/console', PackageManager::Composer);

        expect($found)->toHaveCount(2);
    });

    it('returns empty array when no versions exist for name and manager', function () {
        $found = $this->repo->findByNameAndManager('nonexistent', PackageManager::Composer);
        expect($found)->toHaveCount(0);
    });

    it('finds latest by name and manager', function () {
        $v1 = DependencyVersion::create('symfony/console', PackageManager::Composer, '6.0.0', isLatest: false);
        $v2 = DependencyVersion::create('symfony/console', PackageManager::Composer, '7.0.0', isLatest: true);

        $this->repo->save($v1);
        $this->repo->save($v2);

        $latest = $this->repo->findLatestByNameAndManager('symfony/console', PackageManager::Composer);

        expect($latest)->not->toBeNull();
        expect($latest->getVersion())->toBe('7.0.0');
        expect($latest->isLatest())->toBeTrue();
    });

    it('returns null when no latest version exists', function () {
        $v1 = DependencyVersion::create('symfony/console', PackageManager::Composer, '6.0.0', isLatest: false);
        $this->repo->save($v1);

        $latest = $this->repo->findLatestByNameAndManager('symfony/console', PackageManager::Composer);

        expect($latest)->toBeNull();
    });

    it('finds by name, manager, and version', function () {
        $v1 = DependencyVersion::create('symfony/console', PackageManager::Composer, '6.0.0');
        $this->repo->save($v1);

        $found = $this->repo->findByNameManagerAndVersion('symfony/console', PackageManager::Composer, '6.0.0');

        expect($found)->not->toBeNull();
        expect($found->getVersion())->toBe('6.0.0');
    });

    it('returns null for non-existent version', function () {
        $found = $this->repo->findByNameManagerAndVersion('symfony/console', PackageManager::Composer, '99.0.0');
        expect($found)->toBeNull();
    });

    it('clears latest flag for given name and manager', function () {
        $v1 = DependencyVersion::create('symfony/console', PackageManager::Composer, '6.0.0', isLatest: true);
        $v2 = DependencyVersion::create('symfony/console', PackageManager::Composer, '7.0.0', isLatest: true);
        $v3 = DependencyVersion::create('react', PackageManager::Npm, '18.0.0', isLatest: true);

        $this->repo->save($v1);
        $this->repo->save($v2);
        $this->repo->save($v3);

        $this->repo->clearLatestFlag('symfony/console', PackageManager::Composer);
        $this->getEntityManager()->clear();

        // Symfony versions should no longer be latest
        $found1 = $this->repo->findByNameManagerAndVersion('symfony/console', PackageManager::Composer, '6.0.0');
        expect($found1->isLatest())->toBeFalse();

        $found2 = $this->repo->findByNameManagerAndVersion('symfony/console', PackageManager::Composer, '7.0.0');
        expect($found2->isLatest())->toBeFalse();

        // React version should still be latest (different package)
        $found3 = $this->repo->findByNameManagerAndVersion('react', PackageManager::Npm, '18.0.0');
        expect($found3->isLatest())->toBeTrue();
    });
});
