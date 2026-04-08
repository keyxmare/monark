<?php

declare(strict_types=1);

use App\Shared\Domain\ValueObject\PackageManager;
use App\VersionRegistry\Domain\Model\ProductVersion;

describe('ProductVersion', function () {
    it('creates a product version with factory method', function () {
        $version = ProductVersion::create(
            productName: 'symfony',
            version: '7.2.5',
            releaseDate: new DateTimeImmutable('2025-03-15'),
            isLts: true,
            isLatest: true,
            eolDate: '2028-11-01',
        );

        expect($version->getProductName())->toBe('symfony');
        expect($version->getVersion())->toBe('7.2.5');
        expect($version->isLts())->toBeTrue();
        expect($version->isLatest())->toBeTrue();
        expect($version->getEolDate())->toBe('2028-11-01');
        expect($version->getPackageManager())->toBeNull();
    });

    it('creates a package version with package manager', function () {
        $version = ProductVersion::create(
            productName: 'vue',
            version: '3.5.13',
            packageManager: PackageManager::Npm,
        );

        expect($version->getPackageManager())->toBe(PackageManager::Npm);
    });

    it('updates latest flag', function () {
        $version = ProductVersion::create(
            productName: 'php',
            version: '8.4.0',
            isLatest: true,
        );

        $version->markAsLatest(false);
        expect($version->isLatest())->toBeFalse();
    });
});
