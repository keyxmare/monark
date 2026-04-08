<?php

declare(strict_types=1);

use App\Shared\Domain\DTO\OsvQuery;
use App\Shared\Domain\DTO\OsvVulnerability;
use App\Shared\Domain\ValueObject\Severity;

describe('OsvQuery', function () {
    it('creates a query with ecosystem, name and version', function () {
        $query = new OsvQuery(
            ecosystem: 'Packagist',
            name: 'symfony/http-kernel',
            version: '6.4.1',
        );

        expect($query->ecosystem)->toBe('Packagist');
        expect($query->name)->toBe('symfony/http-kernel');
        expect($query->version)->toBe('6.4.1');
    });
});

describe('OsvVulnerability', function () {
    it('creates a vulnerability DTO', function () {
        $vuln = new OsvVulnerability(
            id: 'GHSA-xxxx-yyyy',
            cveId: 'CVE-2026-12345',
            summary: 'Remote code execution',
            severity: Severity::Critical,
            cvssScore: 9.8,
            patchedVersion: '6.4.2',
            references: ['https://github.com/advisory/GHSA-xxxx-yyyy'],
            publishedAt: new DateTimeImmutable('2026-03-01'),
        );

        expect($vuln->id)->toBe('GHSA-xxxx-yyyy');
        expect($vuln->cveId)->toBe('CVE-2026-12345');
        expect($vuln->severity)->toBe(Severity::Critical);
        expect($vuln->cvssScore)->toBe(9.8);
        expect($vuln->patchedVersion)->toBe('6.4.2');
    });

    it('handles null cveId and cvssScore', function () {
        $vuln = new OsvVulnerability(
            id: 'PYSEC-2026-001',
            cveId: null,
            summary: 'Denial of service',
            severity: Severity::Medium,
            cvssScore: null,
            patchedVersion: null,
            references: [],
            publishedAt: new DateTimeImmutable('2026-03-15'),
        );

        expect($vuln->cveId)->toBeNull();
        expect($vuln->cvssScore)->toBeNull();
        expect($vuln->patchedVersion)->toBeNull();
    });
});
