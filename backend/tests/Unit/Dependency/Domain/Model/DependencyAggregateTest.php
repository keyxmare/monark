<?php

declare(strict_types=1);

use App\Dependency\Domain\Event\DependencyCreated;
use App\Dependency\Domain\Event\DependencyUpgraded;
use App\Dependency\Domain\Event\VulnerabilityDetected;
use App\Dependency\Domain\Event\VulnerabilityResolved;
use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\RegistryStatus;
use App\Dependency\Domain\Model\Severity;
use App\Dependency\Domain\ValueObject\CveId;
use App\Dependency\Domain\ValueObject\SemanticVersion;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use Symfony\Component\Uid\Uuid;

function createAggregateDependency(string $currentVersion = '1.0.0', string $latestVersion = '1.0.0'): Dependency
{
    return Dependency::create(
        name: 'symfony/console',
        currentVersion: $currentVersion,
        latestVersion: $latestVersion,
        ltsVersion: $latestVersion,
        packageManager: PackageManager::Composer,
        type: DependencyType::Runtime,
        isOutdated: false,
        projectId: Uuid::v4(),
    );
}

describe('Dependency Aggregate', function () {
    describe('create', function () {
        it('records DependencyCreated event', function () {
            $dep = \createAggregateDependency();
            $events = $dep->pullDomainEvents();

            expect($events)->toHaveCount(1)
                ->and($events[0])->toBeInstanceOf(DependencyCreated::class)
                ->and($events[0]->name)->toBe('symfony/console')
                ->and($events[0]->packageManager)->toBe('composer');
        });
    });

    describe('upgrade', function () {
        it('updates version and records event', function () {
            $dep = \createAggregateDependency('1.0.0', '2.0.0');
            $dep->pullDomainEvents();

            $dep->upgrade(SemanticVersion::parse('2.0.0'));

            expect($dep->getCurrentVersion())->toBe('2.0.0');
            $events = $dep->pullDomainEvents();
            expect($events)->toHaveCount(1)
                ->and($events[0])->toBeInstanceOf(DependencyUpgraded::class)
                ->and($events[0]->previousVersion)->toBe('1.0.0')
                ->and($events[0]->newVersion)->toBe('2.0.0')
                ->and($events[0]->gapType)->toBe('major');
        });

        it('detects minor gap type', function () {
            $dep = \createAggregateDependency('1.0.0', '1.3.0');
            $dep->pullDomainEvents();

            $dep->upgrade(SemanticVersion::parse('1.3.0'));

            $events = $dep->pullDomainEvents();
            expect($events[0]->gapType)->toBe('minor');
        });

        it('does nothing when version is same', function () {
            $dep = \createAggregateDependency('1.0.0', '1.0.0');
            $dep->pullDomainEvents();

            $dep->upgrade(SemanticVersion::parse('1.0.0'));

            expect($dep->pullDomainEvents())->toBeEmpty();
        });
    });

    describe('reportVulnerability', function () {
        it('adds vulnerability and records event', function () {
            $dep = \createAggregateDependency();
            $dep->pullDomainEvents();

            $dep->reportVulnerability(
                cveId: 'CVE-2024-12345',
                severity: Severity::High,
                title: 'Test vuln',
                description: 'A test vulnerability',
                patchedVersion: '1.0.1',
            );

            expect($dep->getVulnerabilityCount())->toBe(1);
            $events = $dep->pullDomainEvents();
            expect($events)->toHaveCount(1)
                ->and($events[0])->toBeInstanceOf(VulnerabilityDetected::class)
                ->and($events[0]->cveId)->toBe('CVE-2024-12345')
                ->and($events[0]->severity)->toBe('high');
        });

        it('rejects duplicate CVE', function () {
            $dep = \createAggregateDependency();

            $dep->reportVulnerability(
                cveId: 'CVE-2024-12345',
                severity: Severity::High,
                title: 'First',
                description: 'First description',
                patchedVersion: '1.0.1',
            );

            $dep->reportVulnerability(
                cveId: 'CVE-2024-12345',
                severity: Severity::Critical,
                title: 'Duplicate',
                description: 'Duplicate description',
                patchedVersion: '1.0.2',
            );

            expect($dep->getVulnerabilityCount())->toBe(1);
        });
    });

    describe('resolveVulnerability', function () {
        it('marks vulnerability as fixed and records event', function () {
            $dep = \createAggregateDependency();
            $dep->reportVulnerability(
                cveId: 'CVE-2024-12345',
                severity: Severity::High,
                title: 'Test vuln',
                description: 'Desc',
                patchedVersion: '1.0.1',
            );
            $dep->pullDomainEvents();

            $dep->resolveVulnerability(CveId::fromString('CVE-2024-12345'), '1.0.1');

            $events = $dep->pullDomainEvents();
            expect($events)->toHaveCount(1)
                ->and($events[0])->toBeInstanceOf(VulnerabilityResolved::class)
                ->and($events[0]->cveId)->toBe('CVE-2024-12345')
                ->and($events[0]->patchedVersion)->toBe('1.0.1');
        });
    });

    describe('markDeprecated', function () {
        it('transitions to deprecated status', function () {
            $dep = \createAggregateDependency();

            $dep->markDeprecated();

            expect($dep->getRegistryStatus())->toBe(RegistryStatus::Deprecated);
        });
    });

    describe('markSynced', function () {
        it('transitions to synced status', function () {
            $dep = \createAggregateDependency();

            $dep->markSynced();

            expect($dep->getRegistryStatus())->toBe(RegistryStatus::Synced);
        });
    });

    describe('getSemanticCurrentVersion', function () {
        it('returns parsed SemanticVersion', function () {
            $dep = \createAggregateDependency('2.3.1');

            $sv = $dep->getSemanticCurrentVersion();

            expect($sv)->toBeInstanceOf(SemanticVersion::class)
                ->and($sv->major)->toBe(2)
                ->and($sv->minor)->toBe(3);
        });

        it('returns null for unparseable version', function () {
            $dep = \createAggregateDependency('dev-main');

            expect($dep->getSemanticCurrentVersion())->toBeNull();
        });
    });
});
