<?php

declare(strict_types=1);

namespace App\Dependency\Infrastructure\Registry;

use App\Dependency\Domain\DTO\RegistryVersion;
use App\Dependency\Domain\Port\PackageRegistryPort;
use App\Dependency\Infrastructure\Registry\Attribute\AsPackageRegistry;
use App\Shared\Domain\ValueObject\PackageManager;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

#[AsPackageRegistry(PackageManager::Npm)]
final readonly class NpmRegistryAdapter implements PackageRegistryPort
{
    private const string BASE_URL = 'https://registry.npmjs.org';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function supports(PackageManager $manager): bool
    {
        return $manager === PackageManager::Npm;
    }

    public function fetchVersions(string $packageName, PackageManager $manager, ?string $sinceVersion = null): array
    {
        try {
            $response = $this->httpClient->request('GET', \sprintf('%s/%s', self::BASE_URL, $packageName), [
                'headers' => ['Accept' => 'application/json'],
            ]);
            $data = $response->toArray();
        } catch (\Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                $this->logger->debug('npm package not found: {package}', ['package' => $packageName]);
            } else {
                $this->logger->error('npm registry fetch failed for {package}: {error}', [
                    'package' => $packageName,
                    'error' => $e->getMessage(),
                ]);
            }

            return [];
        } catch (Throwable $e) {
            $this->logger->error('npm registry fetch failed for {package}: {error}', [
                'package' => $packageName,
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        /** @var array<string, string> $times */
        $times = \is_array($data['time'] ?? null) ? $data['time'] : [];
        /** @var array<string, string> $distTags */
        $distTags = \is_array($data['dist-tags'] ?? null) ? $data['dist-tags'] : [];
        $latest = $distTags['latest'] ?? null;
        unset($data);

        $versions = [];
        $sinceReached = $sinceVersion === null;

        foreach ($times as $version => $dateStr) {
            if ($version === 'created' || $version === 'modified') {
                continue;
            }

            if (!$sinceReached) {
                if (\version_compare($version, $sinceVersion, '>')) {
                    $sinceReached = true;
                } else {
                    continue;
                }
            }

            $releaseDate = null;
            try {
                $releaseDate = new DateTimeImmutable($dateStr);
            } catch (Throwable) {
            }

            $versions[] = new RegistryVersion(
                version: $version,
                releaseDate: $releaseDate,
                isLatest: $version === $latest,
            );
        }

        return $versions;
    }
}
