<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Osv;

use App\Shared\Domain\DTO\OsvQuery;
use App\Shared\Domain\DTO\OsvVulnerability;
use App\Shared\Domain\Port\OsvClientInterface;
use App\Shared\Domain\ValueObject\Severity;
use DateTimeImmutable;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class OsvApiClient implements OsvClientInterface
{
    private const string BASE_URL = 'https://api.osv.dev/v1';
    private const int BATCH_LIMIT = 1000;

    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    /** @return list<OsvVulnerability> */
    public function queryPackage(string $ecosystem, string $name, string $version): array
    {
        $response = $this->httpClient->request('POST', self::BASE_URL . '/query', [
            'json' => [
                'package' => ['name' => $name, 'ecosystem' => $ecosystem],
                'version' => $version,
            ],
            'timeout' => 30,
        ]);

        /** @var array{vulns?: list<array{id: string, aliases?: list<string>, summary?: string, details?: string, severity?: list<array{type?: string, score?: string}>, database_specific?: array{cvss_score?: float}, affected?: list<array{ranges?: list<array{events?: list<array{fixed?: string}>}>}>, references?: list<array{url?: string}>, published?: string}>} $data */
        $data = $response->toArray(false);

        return $this->mapVulnerabilities($data['vulns'] ?? []);
    }

    /**
     * @param list<OsvQuery> $queries
     * @return list<list<OsvVulnerability>>
     */
    public function queryBatch(array $queries): array
    {
        if ($queries === []) {
            return [];
        }

        $allResults = [];
        $chunks = \array_chunk($queries, self::BATCH_LIMIT);

        foreach ($chunks as $chunk) {
            $payload = \array_map(
                static fn (OsvQuery $q) => [
                    'package' => ['name' => $q->name, 'ecosystem' => $q->ecosystem],
                    'version' => $q->version,
                ],
                $chunk,
            );

            $response = $this->httpClient->request('POST', self::BASE_URL . '/querybatch', [
                'json' => ['queries' => $payload],
                'timeout' => 60,
            ]);

            /** @var array{results?: list<array{vulns?: list<array{id: string, aliases?: list<string>, summary?: string, details?: string, severity?: list<array{type?: string, score?: string}>, database_specific?: array{cvss_score?: float}, affected?: list<array{ranges?: list<array{events?: list<array{fixed?: string}>}>}>, references?: list<array{url?: string}>, published?: string}>}>} $data */
            $data = $response->toArray(false);
            foreach ($data['results'] ?? [] as $result) {
                $allResults[] = $this->mapVulnerabilities($result['vulns'] ?? []);
            }
        }

        return $allResults;
    }

    /**
     * @param list<array{id: string, aliases?: list<string>, summary?: string, details?: string, severity?: list<array{type?: string, score?: string}>, database_specific?: array{cvss_score?: float}, affected?: list<array{ranges?: list<array{events?: list<array{fixed?: string}>}>}>, references?: list<array{url?: string}>, published?: string}> $rawVulns
     * @return list<OsvVulnerability>
     */
    private function mapVulnerabilities(array $rawVulns): array
    {
        $results = [];

        foreach ($rawVulns as $raw) {
            $cveId = $this->extractCveAlias($raw['aliases'] ?? []);
            $cvssScore = $this->extractCvssScore($raw);
            $severity = $cvssScore !== null
                ? Severity::fromCvssScore($cvssScore)
                : Severity::Medium;

            $results[] = new OsvVulnerability(
                id: $raw['id'],
                cveId: $cveId,
                summary: $raw['summary'] ?? $raw['details'] ?? '',
                severity: $severity,
                cvssScore: $cvssScore,
                patchedVersion: $this->extractPatchedVersion($raw['affected'] ?? []),
                references: $this->extractReferences($raw['references'] ?? []),
                publishedAt: new DateTimeImmutable($raw['published'] ?? 'now'),
            );
        }

        return $results;
    }

    /** @param list<string> $aliases */
    private function extractCveAlias(array $aliases): ?string
    {
        foreach ($aliases as $alias) {
            if (\str_starts_with($alias, 'CVE-')) {
                return $alias;
            }
        }

        return null;
    }

    /** @param array{severity?: list<array{type?: string, score?: string}>, database_specific?: array{cvss_score?: float}} $raw */
    private function extractCvssScore(array $raw): ?float
    {
        if (isset($raw['database_specific']['cvss_score'])) {
            return (float) $raw['database_specific']['cvss_score'];
        }

        foreach ($raw['severity'] ?? [] as $entry) {
            if (($entry['type'] ?? '') === 'CVSS_V3' && isset($entry['score'])) {
                $parts = \explode('/', $entry['score']);
                foreach ($parts as $part) {
                    if (\is_numeric($part)) {
                        return (float) $part;
                    }
                }
            }
        }

        return null;
    }

    /** @param list<array{ranges?: list<array{events?: list<array{fixed?: string}>}>}> $affected */
    private function extractPatchedVersion(array $affected): ?string
    {
        foreach ($affected as $entry) {
            foreach ($entry['ranges'] ?? [] as $range) {
                foreach ($range['events'] ?? [] as $event) {
                    if (isset($event['fixed']) && $event['fixed'] !== '') {
                        return $event['fixed'];
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param list<array{url?: string}> $references
     * @return list<string>
     */
    private function extractReferences(array $references): array
    {
        $urls = [];
        foreach ($references as $ref) {
            if (isset($ref['url'])) {
                $urls[] = $ref['url'];
            }
        }

        return $urls;
    }
}
