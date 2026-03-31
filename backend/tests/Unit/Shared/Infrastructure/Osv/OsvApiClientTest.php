<?php

declare(strict_types=1);

use App\Shared\Domain\DTO\OsvQuery;
use App\Shared\Domain\ValueObject\Severity;
use App\Shared\Infrastructure\Osv\OsvApiClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

function createOsvResponse(array $vulns = []): string
{
    return \json_encode(['vulns' => $vulns], JSON_THROW_ON_ERROR);
}

function createBatchOsvResponse(array $results = []): string
{
    return \json_encode(['results' => $results], JSON_THROW_ON_ERROR);
}

function sampleOsvVuln(
    string $id = 'GHSA-xxxx',
    ?string $cve = 'CVE-2026-12345',
    float $cvss = 9.8,
    ?string $fixed = '6.4.2',
): array {
    $aliases = $cve !== null ? [$cve] : [];
    return [
        'id' => $id,
        'summary' => 'Test vulnerability',
        'aliases' => $aliases,
        'severity' => [['type' => 'CVSS_V3', 'score' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H']],
        'database_specific' => ['cvss_score' => $cvss],
        'affected' => [
            [
                'ranges' => [['type' => 'ECOSYSTEM', 'events' => [['introduced' => '0'], ['fixed' => $fixed ?? '']]]],
            ],
        ],
        'references' => [['type' => 'ADVISORY', 'url' => 'https://example.com']],
        'published' => '2026-03-01T00:00:00Z',
    ];
}

describe('OsvApiClient', function () {
    it('queries a single package', function () {
        $response = new MockResponse(createOsvResponse([sampleOsvVuln()]));
        $httpClient = new MockHttpClient([$response]);
        $client = new OsvApiClient($httpClient);

        $results = $client->queryPackage('Packagist', 'symfony/http-kernel', '6.4.1');

        expect($results)->toHaveCount(1);
        expect($results[0]->id)->toBe('GHSA-xxxx');
        expect($results[0]->cveId)->toBe('CVE-2026-12345');
        expect($results[0]->severity)->toBe(Severity::Critical);
        expect($results[0]->patchedVersion)->toBe('6.4.2');
    });

    it('returns empty array when no vulnerabilities', function () {
        $response = new MockResponse(\json_encode(new \stdClass()));
        $httpClient = new MockHttpClient([$response]);
        $client = new OsvApiClient($httpClient);

        $results = $client->queryPackage('npm', 'safe-package', '1.0.0');

        expect($results)->toBeEmpty();
    });

    it('queries batch', function () {
        $response = new MockResponse(createBatchOsvResponse([
            ['vulns' => [sampleOsvVuln('GHSA-1111', 'CVE-2026-0001', 7.5, '2.0.0')]],
            ['vulns' => []],
        ]));
        $httpClient = new MockHttpClient([$response]);
        $client = new OsvApiClient($httpClient);

        $results = $client->queryBatch([
            new OsvQuery('Packagist', 'vendor/pkg-a', '1.0.0'),
            new OsvQuery('npm', 'safe-pkg', '3.0.0'),
        ]);

        expect($results)->toHaveCount(2);
        expect($results[0])->toHaveCount(1);
        expect($results[0][0]->cveId)->toBe('CVE-2026-0001');
        expect($results[1])->toBeEmpty();
    });

    it('handles vulnerability without CVE alias', function () {
        $vuln = sampleOsvVuln('PYSEC-2026-001', null, 5.0, null);
        $response = new MockResponse(createOsvResponse([$vuln]));
        $httpClient = new MockHttpClient([$response]);
        $client = new OsvApiClient($httpClient);

        $results = $client->queryPackage('PyPI', 'some-lib', '1.0.0');

        expect($results[0]->cveId)->toBeNull();
        expect($results[0]->id)->toBe('PYSEC-2026-001');
        expect($results[0]->severity)->toBe(Severity::Medium);
    });
});
