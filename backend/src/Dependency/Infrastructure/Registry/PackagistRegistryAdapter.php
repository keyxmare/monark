<?php

declare(strict_types=1);

namespace App\Dependency\Infrastructure\Registry;

use App\Dependency\Domain\DTO\RegistryVersion;
use App\Dependency\Domain\Port\PackageRegistryPort;
use App\Shared\Domain\ValueObject\PackageManager;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class PackagistRegistryAdapter implements PackageRegistryPort
{
    private const string BASE_URL = 'https://repo.packagist.org/p2';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function supports(PackageManager $manager): bool
    {
        return $manager === PackageManager::Composer;
    }

    public function fetchVersions(string $packageName, PackageManager $manager, ?string $sinceVersion = null): array
    {
        try {
            $response = $this->httpClient->request('GET', \sprintf('%s/%s.json', self::BASE_URL, $packageName));
            $data = $response->toArray();
        } catch (\Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                $this->logger->debug('Packagist package not found: {package}', ['package' => $packageName]);
            } else {
                $this->logger->error('Packagist fetch failed for {package}: {error}', [
                    'package' => $packageName,
                    'error' => $e->getMessage(),
                ]);
            }

            return [];
        } catch (\Throwable $e) {
            $this->logger->error('Packagist fetch failed for {package}: {error}', [
                'package' => $packageName,
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        /** @var list<array{version?: string, version_normalized?: string, time?: string}> $entries */
        $entries = $data['packages'][$packageName] ?? [];
        unset($data);

        $versions = [];
        $isFirst = true;

        foreach ($entries as $entry) {
            $version = \ltrim($entry['version'] ?? '', 'v');
            if ($version === '' || \str_contains($version, 'dev') || \str_contains($version, 'alpha') || \str_contains($version, 'beta') || \str_contains($version, 'RC')) {
                continue;
            }

            if ($sinceVersion !== null && \version_compare($version, $sinceVersion, '<=')) {
                continue;
            }

            $releaseDate = null;
            if (isset($entry['time'])) {
                try {
                    $releaseDate = new DateTimeImmutable($entry['time']);
                } catch (\Throwable) {
                }
            }

            $versions[] = new RegistryVersion(
                version: $version,
                releaseDate: $releaseDate,
                isLatest: $isFirst,
            );
            $isFirst = false;
        }

        return $versions;
    }
}
