<?php

declare(strict_types=1);

namespace App\Dependency\Infrastructure\Registry;

use App\Dependency\Domain\DTO\RegistryVersion;
use App\Dependency\Domain\Port\PackageRegistryPort;
use App\Dependency\Infrastructure\Registry\Attribute\AsPackageRegistry;
use App\Shared\Domain\ValueObject\PackageManager;
use DateTimeImmutable;
use Override;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

#[AsPackageRegistry(PackageManager::Pip)]
final readonly class PypiRegistryAdapter implements PackageRegistryPort
{
    private const string BASE_URL = 'https://pypi.org/pypi';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    #[Override]
    public function supports(PackageManager $manager): bool
    {
        return $manager === PackageManager::Pip;
    }

    #[Override]
    public function fetchVersions(string $packageName, PackageManager $manager, ?string $sinceVersion = null): array
    {
        try {
            $response = $this->httpClient->request('GET', \sprintf('%s/%s/json', self::BASE_URL, $packageName));
            $data = $response->toArray();
        } catch (\Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                $this->logger->debug('PyPI package not found: {package}', ['package' => $packageName]);
            } else {
                $this->logger->error('PyPI fetch failed for {package}: {error}', [
                    'package' => $packageName,
                    'error' => $e->getMessage(),
                ]);
            }

            return [];
        } catch (Throwable $e) {
            $this->logger->error('PyPI fetch failed for {package}: {error}', [
                'package' => $packageName,
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        /** @var string $latest */
        $latest = \is_array($data['info'] ?? null) ? ($data['info']['version'] ?? '') : '';
        /** @var array<string, list<array{upload_time?: string}>> $releases */
        $releases = \is_array($data['releases'] ?? null) ? $data['releases'] : [];
        unset($data);

        $versions = [];

        foreach ($releases as $version => $files) {
            if ($files === []) {
                continue;
            }

            if ($sinceVersion !== null && \version_compare($version, $sinceVersion, '<=')) {
                continue;
            }

            $releaseDate = null;
            $uploadTime = $files[0]['upload_time'] ?? null;
            if ($uploadTime !== null) {
                try {
                    $releaseDate = new DateTimeImmutable($uploadTime);
                } catch (Throwable) {
                }
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
