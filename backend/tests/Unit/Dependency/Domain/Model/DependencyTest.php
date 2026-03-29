<?php

declare(strict_types=1);

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\RegistryStatus;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use App\Tests\Factory\Dependency\DependencyFactory;
use App\Tests\Factory\Dependency\VulnerabilityFactory;
use Symfony\Component\Uid\Uuid;

describe('Dependency', function () {
    it('creates with all properties', function () {
        $projectId = Uuid::v7();
        $dependency = Dependency::create(
            name: 'lodash',
            currentVersion: '4.17.0',
            latestVersion: '4.18.0',
            ltsVersion: '4.17.21',
            packageManager: PackageManager::Npm,
            type: DependencyType::Runtime,
            isOutdated: true,
            projectId: $projectId,
            repositoryUrl: 'https://github.com/lodash/lodash',
        );

        expect($dependency->getId())->toBeInstanceOf(Uuid::class);
        expect($dependency->getName())->toBe('lodash');
        expect($dependency->getCurrentVersion())->toBe('4.17.0');
        expect($dependency->getLatestVersion())->toBe('4.18.0');
        expect($dependency->getLtsVersion())->toBe('4.17.21');
        expect($dependency->getPackageManager())->toBe(PackageManager::Npm);
        expect($dependency->getType())->toBe(DependencyType::Runtime);
        expect($dependency->isOutdated())->toBeTrue();
        expect($dependency->getProjectId())->toBe($projectId);
        expect($dependency->getRepositoryUrl())->toBe('https://github.com/lodash/lodash');
        expect($dependency->getRegistryStatus())->toBe(RegistryStatus::Pending);
        expect($dependency->getVulnerabilityCount())->toBe(0);
        expect($dependency->getCreatedAt())->toBeInstanceOf(DateTimeImmutable::class);
        expect($dependency->getUpdatedAt())->toBeInstanceOf(DateTimeImmutable::class);
    });

    it('creates without optional repositoryUrl', function () {
        $dependency = DependencyFactory::create();

        expect($dependency->getRepositoryUrl())->toBeNull();
    });

    it('marks registry status and updates updatedAt', function () {
        $dependency = DependencyFactory::create();
        $previousUpdatedAt = $dependency->getUpdatedAt();

        usleep(1000);
        $dependency->markRegistryStatus(RegistryStatus::Synced);

        expect($dependency->getRegistryStatus())->toBe(RegistryStatus::Synced);
        expect($dependency->getUpdatedAt())->not->toBe($previousUpdatedAt);
    });

    it('updates a single field and refreshes updatedAt', function () {
        $dependency = DependencyFactory::create();
        $previousUpdatedAt = $dependency->getUpdatedAt();

        usleep(1000);
        $dependency->update(name: 'symfony/console');

        expect($dependency->getName())->toBe('symfony/console');
        expect($dependency->getCurrentVersion())->toBe('7.2.0');
        expect($dependency->getUpdatedAt())->not->toBe($previousUpdatedAt);
    });

    it('updates all fields', function () {
        $dependency = DependencyFactory::create();

        $dependency->update(
            name: 'vue',
            currentVersion: '3.4.0',
            latestVersion: '3.5.0',
            ltsVersion: '3.2.0',
            packageManager: PackageManager::Npm,
            type: DependencyType::Dev,
            isOutdated: false,
            repositoryUrl: 'https://github.com/vuejs/core',
        );

        expect($dependency->getName())->toBe('vue');
        expect($dependency->getCurrentVersion())->toBe('3.4.0');
        expect($dependency->getLatestVersion())->toBe('3.5.0');
        expect($dependency->getLtsVersion())->toBe('3.2.0');
        expect($dependency->getPackageManager())->toBe(PackageManager::Npm);
        expect($dependency->getType())->toBe(DependencyType::Dev);
        expect($dependency->isOutdated())->toBeFalse();
        expect($dependency->getRepositoryUrl())->toBe('https://github.com/vuejs/core');
    });

    it('clears repositoryUrl with clearRepositoryUrl flag', function () {
        $projectId = Uuid::v7();
        $dependency = Dependency::create(
            name: 'lodash',
            currentVersion: '4.17.0',
            latestVersion: '4.18.0',
            ltsVersion: '4.17.21',
            packageManager: PackageManager::Npm,
            type: DependencyType::Runtime,
            isOutdated: false,
            projectId: $projectId,
            repositoryUrl: 'https://github.com/lodash/lodash',
        );

        $dependency->update(clearRepositoryUrl: true);

        expect($dependency->getRepositoryUrl())->toBeNull();
    });

    it('gives repositoryUrl precedence over clearRepositoryUrl', function () {
        $dependency = DependencyFactory::create();

        $dependency->update(
            repositoryUrl: 'https://github.com/new/repo',
            clearRepositoryUrl: true,
        );

        expect($dependency->getRepositoryUrl())->toBe('https://github.com/new/repo');
    });

    it('returns vulnerability count from collection', function () {
        $dependency = DependencyFactory::create();
        $vuln1 = VulnerabilityFactory::create($dependency);
        $vuln2 = VulnerabilityFactory::create($dependency, ['cveId' => 'CVE-2026-99999']);

        $ref = new ReflectionProperty($dependency, 'vulnerabilities');
        $collection = $ref->getValue($dependency);
        $collection->add($vuln1);
        $collection->add($vuln2);

        expect($dependency->getVulnerabilityCount())->toBe(2);
    });

    it('returns vulnerabilities collection', function () {
        $dependency = DependencyFactory::create();
        expect($dependency->getVulnerabilities())->toBeInstanceOf(\Doctrine\Common\Collections\Collection::class)
            ->toHaveCount(0);
    });
});
