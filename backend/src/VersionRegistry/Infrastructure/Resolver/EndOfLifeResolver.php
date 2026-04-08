<?php

declare(strict_types=1);

namespace App\VersionRegistry\Infrastructure\Resolver;

use App\VersionRegistry\Domain\DTO\ResolvedVersion;
use App\VersionRegistry\Domain\Port\VersionResolverInterface;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

final readonly class EndOfLifeResolver implements VersionResolverInterface
{
    private const string BASE_URL = 'https://endoflife.date/api';

    private const array SLUG_MAP = [
        'php' => 'php',
        'python' => 'python',
        'nodejs' => 'nodejs',
        'ruby' => 'ruby',
        'go' => 'go',
        'rust' => 'rust',
        'symfony' => 'symfony',
        'laravel' => 'laravel',
        'vue' => 'vue',
        'nuxt' => 'nuxt',
        'angular' => 'angular',
        'react' => 'react',
        'next.js' => 'nextjs',
        'django' => 'django',
        'rails' => 'ruby-on-rails',
    ];

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function supports(string $resolverSource): bool
    {
        return $resolverSource === 'endoflife';
    }

    public function fetchVersions(string $productName, ?DateTimeImmutable $since = null): array
    {
        $slug = self::SLUG_MAP[\strtolower($productName)] ?? null;
        if ($slug === null) {
            return [];
        }

        try {
            $response = $this->httpClient->request('GET', \sprintf('%s/%s.json', self::BASE_URL, $slug));
            /** @var list<array{cycle: string, releaseDate?: string, eol?: string|bool, latest?: string, latestReleaseDate?: string, lts?: bool}> $cycles */
            $cycles = $response->toArray();
        } catch (Throwable $e) {
            $this->logger->warning('endoflife.date fetch failed for {product}: {error}', [
                'product' => $productName,
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        $versions = [];
        $isFirst = true;

        foreach ($cycles as $cycle) {
            $latestVersion = $cycle['latest'] ?? $cycle['cycle'];
            $eol = $cycle['eol'] ?? null;
            $eolDate = \is_string($eol) ? $eol : ($eol === true ? 'true' : null);

            $releaseDate = null;
            $dateStr = $cycle['latestReleaseDate'] ?? $cycle['releaseDate'] ?? null;
            if ($dateStr !== null) {
                try {
                    $releaseDate = new DateTimeImmutable($dateStr);
                } catch (Throwable) {
                }
            }

            $versions[] = new ResolvedVersion(
                version: $latestVersion,
                releaseDate: $releaseDate,
                isLts: (bool) ($cycle['lts'] ?? false),
                isLatest: $isFirst,
                eolDate: $eolDate,
            );

            $isFirst = false;
        }

        return $versions;
    }
}
