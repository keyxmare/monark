<?php

declare(strict_types=1);

use App\Dependency\Domain\ValueObject\SemanticVersion;
use App\VersionRegistry\Domain\ValueObject\ResolvedSemanticVersion;

describe('ResolvedSemanticVersion', function () {
    it('wraps a SemanticVersion with metadata', function () {
        $v = SemanticVersion::parse('5.4.0');
        $rv = new ResolvedSemanticVersion(
            version: $v,
            isLts: true,
            isLatest: true,
        );

        expect($rv->version->__toString())->toBe('5.4.0')
            ->and($rv->isLts)->toBeTrue()
            ->and($rv->isLatest)->toBeTrue();
    });

    it('creates from string version', function () {
        $rv = ResolvedSemanticVersion::fromString('6.0.0', isLts: false, isLatest: true);

        expect($rv->version->major)->toBe(6);
    });

    it('returns null from string when version is invalid', function () {
        $rv = ResolvedSemanticVersion::tryFromString('not-a-version');

        expect($rv)->toBeNull();
    });

    it('is the latest LTS when both flags are true', function () {
        $rv = ResolvedSemanticVersion::fromString('5.4.0', isLts: true, isLatest: true);

        expect($rv->isLatestLts())->toBeTrue();
    });

    it('is not the latest LTS when only latest', function () {
        $rv = ResolvedSemanticVersion::fromString('6.0.0', isLts: false, isLatest: true);

        expect($rv->isLatestLts())->toBeFalse();
    });
});
