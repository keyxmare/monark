<?php

declare(strict_types=1);

namespace App\History\Infrastructure\Resolver;

use App\History\Domain\DTO\ResolvedHistoricalVersion;
use App\History\Domain\Port\HistoricalVersionResolverInterface;
use App\Shared\Domain\ValueObject\PackageManager;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

final readonly class EndOfLifeHistoricalResolver implements HistoricalVersionResolverInterface
{
    private const string BASE_URL = 'https://endoflife.date/api';

    private const array SLUG_MAP = [
        'php' => 'php',
        'python' => 'python',
        'nodejs' => 'nodejs',
        'node' => 'nodejs',
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
        private CacheInterface $cache,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function resolve(string $productName, ?PackageManager $packageManager, DateTimeImmutable $at): ResolvedHistoricalVersion
    {
        $slug = self::SLUG_MAP[\strtolower($productName)] ?? null;
        if ($slug === null) {
            return ResolvedHistoricalVersion::empty();
        }

        $cycles = $this->fetchCycles($slug);
        if ($cycles === []) {
            return ResolvedHistoricalVersion::empty();
        }

        $latest = null;
        $latestDate = null;
        $lts = null;
        $ltsDate = null;

        foreach ($cycles as $cycle) {
            $version = $cycle['latest'] ?? ($cycle['cycle'] ?? null);
            if ($version === null) {
                continue;
            }
            $dateStr = $cycle['latestReleaseDate'] ?? ($cycle['releaseDate'] ?? null);
            if ($dateStr === null) {
                continue;
            }
            try {
                $releaseDate = new DateTimeImmutable($dateStr);
            } catch (Throwable) {
                continue;
            }
            if ($releaseDate > $at) {
                continue;
            }

            if ($latestDate === null || $releaseDate > $latestDate) {
                $latest = (string) $version;
                $latestDate = $releaseDate;
            }

            if (($cycle['lts'] ?? false) === true && ($ltsDate === null || $releaseDate > $ltsDate)) {
                $lts = (string) $version;
                $ltsDate = $releaseDate;
            }
        }

        return new ResolvedHistoricalVersion($latest, $lts);
    }

    /**
     * @return list<array{cycle?: string, releaseDate?: string, latest?: string, latestReleaseDate?: string, lts?: bool|string}>
     */
    private function fetchCycles(string $slug): array
    {
        try {
            return $this->cache->get('history.eol.' . $slug, function (ItemInterface $item) use ($slug): array {
                $item->expiresAfter(86400);
                $response = $this->httpClient->request('GET', \sprintf('%s/%s.json', self::BASE_URL, $slug));

                /** @var list<array{cycle?: string, releaseDate?: string, latest?: string, latestReleaseDate?: string, lts?: bool|string}> $data */
                $data = $response->toArray();

                return $data;
            });
        } catch (Throwable $e) {
            $this->logger->warning('endoflife.date historical fetch failed', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
