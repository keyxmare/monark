<?php

declare(strict_types=1);

use App\Dependency\Domain\Model\DependencyVersion;
use App\Shared\Domain\ValueObject\PackageManager;

describe('DependencyVersion', function () {
    it('creates a version with all properties', function () {
        $releaseDate = new DateTimeImmutable('2025-06-15');
        $version = DependencyVersion::create(
            dependencyName: 'symfony/http-kernel',
            packageManager: PackageManager::Composer,
            version: '7.2.0',
            releaseDate: $releaseDate,
            isLts: true,
            isLatest: true,
        );

        expect($version->getId())->toBeInstanceOf(\Symfony\Component\Uid\Uuid::class);
        expect($version->getDependencyName())->toBe('symfony/http-kernel');
        expect($version->getPackageManager())->toBe(PackageManager::Composer);
        expect($version->getVersion())->toBe('7.2.0');
        expect($version->getReleaseDate())->toBe($releaseDate);
        expect($version->isLts())->toBeTrue();
        expect($version->isLatest())->toBeTrue();
        expect($version->getCreatedAt())->toBeInstanceOf(DateTimeImmutable::class);
    });

    it('creates a version with defaults', function () {
        $version = DependencyVersion::create(
            dependencyName: 'vue',
            packageManager: PackageManager::Npm,
            version: '3.5.0',
        );

        expect($version->getDependencyName())->toBe('vue');
        expect($version->getPackageManager())->toBe(PackageManager::Npm);
        expect($version->getVersion())->toBe('3.5.0');
        expect($version->getReleaseDate())->toBeNull();
        expect($version->isLts())->toBeFalse();
        expect($version->isLatest())->toBeFalse();
    });

    it('marks as latest', function () {
        $version = DependencyVersion::create(
            dependencyName: 'vue',
            packageManager: PackageManager::Npm,
            version: '3.5.0',
            isLatest: false,
        );

        $version->markAsLatest(true);

        expect($version->isLatest())->toBeTrue();
    });

    it('unmarks as latest', function () {
        $version = DependencyVersion::create(
            dependencyName: 'vue',
            packageManager: PackageManager::Npm,
            version: '3.5.0',
            isLatest: true,
        );

        $version->markAsLatest(false);

        expect($version->isLatest())->toBeFalse();
    });
});
