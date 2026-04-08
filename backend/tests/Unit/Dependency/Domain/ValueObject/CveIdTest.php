<?php

declare(strict_types=1);

use App\Dependency\Domain\ValueObject\CveId;

describe('CveId', function () {
    it('parses valid CVE id', function () {
        $cve = CveId::fromString('CVE-2024-12345');

        expect($cve->getYear())->toBe(2024)
            ->and($cve->getSequence())->toBe(12345)
            ->and((string) $cve)->toBe('CVE-2024-12345');
    });

    it('accepts long sequence numbers', function () {
        $cve = CveId::fromString('CVE-2025-1234567');

        expect($cve->getSequence())->toBe(1234567);
    });

    it('throws on invalid format', function () {
        CveId::fromString('GHSA-1234-abcd');
    })->throws(\InvalidArgumentException::class);

    it('throws on too short sequence', function () {
        CveId::fromString('CVE-2024-123');
    })->throws(\InvalidArgumentException::class);

    it('throws on empty string', function () {
        CveId::fromString('');
    })->throws(\InvalidArgumentException::class);

    it('equals identical CVE', function () {
        $a = CveId::fromString('CVE-2024-12345');
        $b = CveId::fromString('CVE-2024-12345');

        expect($a->equals($b))->toBeTrue();
    });

    it('does not equal different CVE', function () {
        $a = CveId::fromString('CVE-2024-12345');
        $b = CveId::fromString('CVE-2024-99999');

        expect($a->equals($b))->toBeFalse();
    });
});
