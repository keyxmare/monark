<?php

declare(strict_types=1);

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\RegistryStatus;
use App\Dependency\Domain\Specification\HasCriticalVulnerabilitySpecification;
use App\Dependency\Domain\Specification\IsDeprecatedSpecification;
use App\Dependency\Domain\Specification\IsOutdatedSpecification;
use App\Shared\Domain\Specification\AndSpecification;
use App\Shared\Domain\ValueObject\Severity;
use App\Tests\Factory\Dependency\DependencyFactory;
use App\Tests\Factory\Dependency\VulnerabilityFactory;

function addVulnerabilityToCollection(Dependency $dependency, mixed $vulnerability): void
{
    $ref = new ReflectionProperty($dependency, 'vulnerabilities');
    $collection = $ref->getValue($dependency);
    $collection->add($vulnerability);
}

describe('IsOutdatedSpecification', function () {
    it('returns true for an outdated dependency', function () {
        $dependency = DependencyFactory::create(['isOutdated' => true]);
        $spec = new IsOutdatedSpecification();

        expect($spec->isSatisfiedBy($dependency))->toBeTrue();
    });

    it('returns false for a non-outdated dependency', function () {
        $dependency = DependencyFactory::create(['isOutdated' => false]);
        $spec = new IsOutdatedSpecification();

        expect($spec->isSatisfiedBy($dependency))->toBeFalse();
    });
});

describe('HasCriticalVulnerabilitySpecification', function () {
    it('returns true when dependency has a critical vulnerability', function () {
        $dependency = DependencyFactory::create();
        $vuln = VulnerabilityFactory::create($dependency, ['severity' => Severity::Critical]);
        \addVulnerabilityToCollection($dependency, $vuln);

        $spec = new HasCriticalVulnerabilitySpecification();

        expect($spec->isSatisfiedBy($dependency))->toBeTrue();
    });

    it('returns false when dependency has only non-critical vulnerabilities', function () {
        $dependency = DependencyFactory::create();
        $vuln = VulnerabilityFactory::create($dependency, ['severity' => Severity::Low]);
        \addVulnerabilityToCollection($dependency, $vuln);

        $spec = new HasCriticalVulnerabilitySpecification();

        expect($spec->isSatisfiedBy($dependency))->toBeFalse();
    });

    it('returns false when dependency has no vulnerabilities', function () {
        $dependency = DependencyFactory::create();
        $spec = new HasCriticalVulnerabilitySpecification();

        expect($spec->isSatisfiedBy($dependency))->toBeFalse();
    });
});

describe('IsDeprecatedSpecification', function () {
    it('returns true for a deprecated dependency', function () {
        $dependency = DependencyFactory::create();
        $dependency->markRegistryStatus(RegistryStatus::Deprecated);

        $spec = new IsDeprecatedSpecification();

        expect($spec->isSatisfiedBy($dependency))->toBeTrue();
    });

    it('returns false for a non-deprecated dependency', function () {
        $dependency = DependencyFactory::create();
        $dependency->markRegistryStatus(RegistryStatus::Synced);

        $spec = new IsDeprecatedSpecification();

        expect($spec->isSatisfiedBy($dependency))->toBeFalse();
    });
});

describe('Specification composition', function () {
    it('combines outdated AND critical vulnerability', function () {
        $dependency = DependencyFactory::create(['isOutdated' => true]);
        $vuln = VulnerabilityFactory::create($dependency, ['severity' => Severity::Critical]);
        \addVulnerabilityToCollection($dependency, $vuln);

        $spec = new AndSpecification([
            new IsOutdatedSpecification(),
            new HasCriticalVulnerabilitySpecification(),
        ]);

        expect($spec->isSatisfiedBy($dependency))->toBeTrue();
    });

    it('fails composition when only one condition is met', function () {
        $dependency = DependencyFactory::create(['isOutdated' => true]);

        $spec = new AndSpecification([
            new IsOutdatedSpecification(),
            new HasCriticalVulnerabilitySpecification(),
        ]);

        expect($spec->isSatisfiedBy($dependency))->toBeFalse();
    });
});
