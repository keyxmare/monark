<?php

declare(strict_types=1);

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\RegistryStatus;
use App\Dependency\Domain\Model\Vulnerability;
use App\Dependency\Domain\Service\DependencyHealthCalculator;
use App\Dependency\Domain\ValueObject\DependencyHealth;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use App\Shared\Domain\ValueObject\Severity;
use App\Shared\Domain\ValueObject\VulnerabilityStatus;
use Symfony\Component\Uid\Uuid;

function createTestDependency(
    string $currentVersion = '1.0.0',
    string $latestVersion = '1.0.0',
    RegistryStatus $registryStatus = RegistryStatus::Synced,
): Dependency {
    $dep = Dependency::create(
        name: 'test/package',
        currentVersion: $currentVersion,
        latestVersion: $latestVersion,
        ltsVersion: $latestVersion,
        packageManager: PackageManager::Composer,
        type: DependencyType::Runtime,
        isOutdated: false,
        projectId: Uuid::v4(),
    );
    $dep->markRegistryStatus($registryStatus);

    return $dep;
}

function addVulnerability(Dependency $dep, Severity $severity = Severity::Medium): void
{
    Vulnerability::create(
        cveId: \sprintf('CVE-2024-%05d', \random_int(10000, 99999)),
        severity: $severity,
        title: 'Test vulnerability',
        description: 'Test description',
        patchedVersion: '999.0.0',
        status: VulnerabilityStatus::Open,
        detectedAt: new \DateTimeImmutable(),
        dependency: $dep,
    );
}

describe('DependencyHealthCalculator', function () {
    it('calculates healthy score for up-to-date dependency', function () {
        $calculator = new DependencyHealthCalculator();
        $dep = \createTestDependency('1.0.0', '1.0.0');

        $health = $calculator->calculate($dep);

        expect($health)->toBeInstanceOf(DependencyHealth::class)
            ->and($health->getScore())->toBe(100)
            ->and($health->isHealthy())->toBeTrue();
    });

    it('penalizes major version gap', function () {
        $calculator = new DependencyHealthCalculator();
        $dep = \createTestDependency('1.0.0', '3.0.0');

        $health = $calculator->calculate($dep);

        expect($health->getScore())->toBeLessThanOrEqual(20)
            ->and($health->isHealthy())->toBeFalse();
    });

    it('penalizes vulnerabilities', function () {
        $calculator = new DependencyHealthCalculator();
        $dep = \createTestDependency('1.0.0', '1.0.0');
        \addVulnerability($dep, Severity::Critical);

        $health = $calculator->calculate($dep);

        expect($health->getScore())->toBeLessThanOrEqual(50)
            ->and($health->isHealthy())->toBeFalse();
    });

    it('penalizes deprecated status', function () {
        $calculator = new DependencyHealthCalculator();
        $dep = \createTestDependency('1.0.0', '1.0.0', registryStatus: RegistryStatus::Deprecated);

        $health = $calculator->calculate($dep);

        expect($health->getScore())->toBeLessThanOrEqual(70);
    });

    it('handles non-parseable versions gracefully', function () {
        $calculator = new DependencyHealthCalculator();
        $dep = \createTestDependency('dev-main', '1.0.0');

        $health = $calculator->calculate($dep);

        expect($health->getScore())->toBeGreaterThanOrEqual(0)
            ->and($health->getScore())->toBeLessThanOrEqual(100);
    });
});
