<?php

declare(strict_types=1);

use App\Dependency\Domain\DTO\RegistryVersion;
use App\Dependency\Domain\Port\PackageRegistryPort;
use App\Dependency\Infrastructure\Registry\PackageRegistryFactory;
use App\Shared\Domain\ValueObject\PackageManager;

describe('PackageRegistryFactory', function () {
    it('delegates to the adapter that supports the package manager', function () {
        $composerAdapter = new class () implements PackageRegistryPort {
            public function supports(PackageManager $manager): bool
            {
                return $manager === PackageManager::Composer;
            }
            public function fetchVersions(string $packageName, PackageManager $manager, ?string $sinceVersion = null): array
            {
                return [new RegistryVersion(version: '8.0.0', isLatest: true)];
            }
        };

        $factory = new PackageRegistryFactory([$composerAdapter]);
        $versions = $factory->fetchVersions('symfony/http-kernel', PackageManager::Composer);

        expect($versions)->toHaveCount(1);
        expect($versions[0]->version)->toBe('8.0.0');
        expect($versions[0]->isLatest)->toBeTrue();
    });

    it('returns empty array when no adapter supports the manager', function () {
        $composerAdapter = new class () implements PackageRegistryPort {
            public function supports(PackageManager $manager): bool
            {
                return $manager === PackageManager::Composer;
            }
            public function fetchVersions(string $packageName, PackageManager $manager, ?string $sinceVersion = null): array
            {
                return [new RegistryVersion(version: '1.0.0')];
            }
        };

        $factory = new PackageRegistryFactory([$composerAdapter]);
        $versions = $factory->fetchVersions('vue', PackageManager::Npm);

        expect($versions)->toBeEmpty();
    });

    it('uses first matching adapter', function () {
        $first = new class () implements PackageRegistryPort {
            public function supports(PackageManager $manager): bool
            {
                return $manager === PackageManager::Npm;
            }
            public function fetchVersions(string $packageName, PackageManager $manager, ?string $sinceVersion = null): array
            {
                return [new RegistryVersion(version: 'first')];
            }
        };
        $second = new class () implements PackageRegistryPort {
            public function supports(PackageManager $manager): bool
            {
                return $manager === PackageManager::Npm;
            }
            public function fetchVersions(string $packageName, PackageManager $manager, ?string $sinceVersion = null): array
            {
                return [new RegistryVersion(version: 'second')];
            }
        };

        $factory = new PackageRegistryFactory([$first, $second]);
        $versions = $factory->fetchVersions('vue', PackageManager::Npm);

        expect($versions[0]->version)->toBe('first');
    });

    it('passes sinceVersion to the adapter', function () {
        $adapter = new class () implements PackageRegistryPort {
            public ?string $receivedSince = null;
            public function supports(PackageManager $manager): bool
            {
                return true;
            }
            public function fetchVersions(string $packageName, PackageManager $manager, ?string $sinceVersion = null): array
            {
                $this->receivedSince = $sinceVersion;
                return [];
            }
        };

        $factory = new PackageRegistryFactory([$adapter]);
        $factory->fetchVersions('pkg', PackageManager::Composer, '7.0.0');

        expect($adapter->receivedSince)->toBe('7.0.0');
    });
});
