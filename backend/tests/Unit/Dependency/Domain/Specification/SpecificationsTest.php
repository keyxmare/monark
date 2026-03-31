<?php

declare(strict_types=1);

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\RegistryStatus;
use App\Shared\Domain\ValueObject\Severity;
use App\Dependency\Domain\Service\DependencyHealthCalculator;
use App\Dependency\Domain\Specification\BelongsToProjectSpecification;
use App\Dependency\Domain\Specification\HasSeverityAboveSpecification;
use App\Dependency\Domain\Specification\HasUnpatchedVulnerabilitySpecification;
use App\Dependency\Domain\Specification\HasVersionGapAboveSpecification;
use App\Dependency\Domain\Specification\HealthBelowSpecification;
use App\Dependency\Domain\Specification\IsDeprecatedSpecification;
use App\Dependency\Domain\Specification\IsOutdatedSpecification;
use App\Dependency\Domain\ValueObject\CveId;
use App\Shared\Domain\Specification\AndSpecification;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Uid\Uuid;

function specTestDep(
    string $currentVersion = '1.0.0',
    string $latestVersion = '1.0.0',
    bool $isOutdated = false,
    RegistryStatus $status = RegistryStatus::Synced,
    ?Uuid $projectId = null,
): Dependency {
    $dep = Dependency::create(
        name: 'test/pkg',
        currentVersion: $currentVersion,
        latestVersion: $latestVersion,
        ltsVersion: $latestVersion,
        packageManager: PackageManager::Composer,
        type: DependencyType::Runtime,
        isOutdated: $isOutdated,
        projectId: $projectId ?? Uuid::v4(),
    );
    $dep->markRegistryStatus($status);

    return $dep;
}

describe('IsOutdatedSpecification', function () {
    it('satisfied when outdated', function () {
        $dep = \specTestDep(isOutdated: true);
        expect((new IsOutdatedSpecification())->isSatisfiedBy($dep))->toBeTrue();
    });

    it('not satisfied when up to date', function () {
        $dep = \specTestDep(isOutdated: false);
        expect((new IsOutdatedSpecification())->isSatisfiedBy($dep))->toBeFalse();
    });

    it('generates Doctrine criteria', function () {
        $criteria = (new IsOutdatedSpecification())->toDoctrineCriteria();
        expect($criteria)->toBeInstanceOf(Criteria::class)
            ->and($criteria->getWhereExpression())->not->toBeNull();
    });
});

describe('IsDeprecatedSpecification', function () {
    it('satisfied when deprecated', function () {
        $dep = \specTestDep(status: RegistryStatus::Deprecated);
        expect((new IsDeprecatedSpecification())->isSatisfiedBy($dep))->toBeTrue();
    });

    it('generates Doctrine criteria', function () {
        $criteria = (new IsDeprecatedSpecification())->toDoctrineCriteria();
        expect($criteria)->toBeInstanceOf(Criteria::class);
    });
});

describe('HasSeverityAboveSpecification', function () {
    it('satisfied when vulnerability above threshold exists', function () {
        $dep = \specTestDep();
        $dep->reportVulnerability('CVE-2024-11111', Severity::Critical, 'Test', 'Desc', '2.0.0');

        expect((new HasSeverityAboveSpecification(Severity::High))->isSatisfiedBy($dep))->toBeTrue();
    });

    it('not satisfied when all vulns below threshold', function () {
        $dep = \specTestDep();
        $dep->reportVulnerability('CVE-2024-22222', Severity::Low, 'Test', 'Desc', '2.0.0');

        expect((new HasSeverityAboveSpecification(Severity::High))->isSatisfiedBy($dep))->toBeFalse();
    });

    it('not satisfied when no vulnerabilities', function () {
        $dep = \specTestDep();
        expect((new HasSeverityAboveSpecification(Severity::Low))->isSatisfiedBy($dep))->toBeFalse();
    });
});

describe('HasVersionGapAboveSpecification', function () {
    it('satisfied when major gap above threshold', function () {
        $dep = \specTestDep('1.0.0', '4.0.0');
        expect((new HasVersionGapAboveSpecification('major', 2))->isSatisfiedBy($dep))->toBeTrue();
    });

    it('not satisfied when gap below threshold', function () {
        $dep = \specTestDep('1.0.0', '2.0.0');
        expect((new HasVersionGapAboveSpecification('major', 2))->isSatisfiedBy($dep))->toBeFalse();
    });

    it('handles non-parseable versions', function () {
        $dep = \specTestDep('dev-main', '2.0.0');
        expect((new HasVersionGapAboveSpecification('major', 1))->isSatisfiedBy($dep))->toBeFalse();
    });
});

describe('HasUnpatchedVulnerabilitySpecification', function () {
    it('satisfied when open vuln exists', function () {
        $dep = \specTestDep();
        $dep->reportVulnerability('CVE-2024-33333', Severity::High, 'Test', 'Desc', '2.0.0');

        expect((new HasUnpatchedVulnerabilitySpecification())->isSatisfiedBy($dep))->toBeTrue();
    });

    it('not satisfied when all resolved', function () {
        $dep = \specTestDep();
        $dep->reportVulnerability('CVE-2024-44444', Severity::High, 'Test', 'Desc', '2.0.0');
        $dep->resolveVulnerability(CveId::fromString('CVE-2024-44444'), '2.0.0');

        expect((new HasUnpatchedVulnerabilitySpecification())->isSatisfiedBy($dep))->toBeFalse();
    });
});

describe('BelongsToProjectSpecification', function () {
    it('satisfied when project matches', function () {
        $projectId = Uuid::v4();
        $dep = \specTestDep(projectId: $projectId);

        expect((new BelongsToProjectSpecification($projectId))->isSatisfiedBy($dep))->toBeTrue();
    });

    it('not satisfied when project differs', function () {
        $dep = \specTestDep(projectId: Uuid::v4());

        expect((new BelongsToProjectSpecification(Uuid::v4()))->isSatisfiedBy($dep))->toBeFalse();
    });
});

describe('HealthBelowSpecification', function () {
    it('satisfied when health score below threshold', function () {
        $dep = \specTestDep('1.0.0', '5.0.0');

        expect((new HealthBelowSpecification(50, new DependencyHealthCalculator()))->isSatisfiedBy($dep))->toBeTrue();
    });

    it('not satisfied when health is good', function () {
        $dep = \specTestDep('1.0.0', '1.0.0');

        expect((new HealthBelowSpecification(50, new DependencyHealthCalculator()))->isSatisfiedBy($dep))->toBeFalse();
    });
});

describe('Specification composition', function () {
    it('composes outdated AND has severity above', function () {
        $dep = \specTestDep('1.0.0', '3.0.0', isOutdated: true);
        $dep->reportVulnerability('CVE-2024-55555', Severity::Critical, 'Test', 'Desc', '2.0.0');

        $spec = new AndSpecification([
            new IsOutdatedSpecification(),
            new HasSeverityAboveSpecification(Severity::High),
        ]);

        expect($spec->isSatisfiedBy($dep))->toBeTrue();
    });

    it('composite generates combined Doctrine criteria', function () {
        $spec = new AndSpecification([
            new IsOutdatedSpecification(),
            new BelongsToProjectSpecification(Uuid::v4()),
        ]);

        $criteria = $spec->toDoctrineCriteria();

        expect($criteria)->toBeInstanceOf(Criteria::class)
            ->and($criteria->getWhereExpression())->not->toBeNull();
    });
});
