<?php

declare(strict_types=1);

namespace App\VersionRegistry\Infrastructure\Resolver;

use App\Dependency\Domain\Port\PackageRegistryResolverPort;
use App\Shared\Domain\ValueObject\PackageManager;
use App\VersionRegistry\Domain\DTO\ResolvedVersion;
use App\VersionRegistry\Domain\Port\VersionResolverInterface;
use DateTimeImmutable;

final readonly class PackageRegistryResolver implements VersionResolverInterface
{
    public function __construct(
        private PackageRegistryResolverPort $registryPort,
    ) {
    }

    public function supports(string $resolverSource): bool
    {
        return $resolverSource === 'registry';
    }

    /**
     * @return list<ResolvedVersion>
     */
    public function fetchVersions(string $productName, ?DateTimeImmutable $since = null, ?PackageManager $packageManager = null): array
    {
        if ($packageManager === null) {
            return [];
        }

        $registryVersions = $this->registryPort->fetchVersions($productName, $packageManager);

        return \array_map(
            static fn ($rv): ResolvedVersion => new ResolvedVersion(
                version: $rv->version,
                releaseDate: $rv->releaseDate,
                isLts: false,
                isLatest: $rv->isLatest,
                eolDate: null,
            ),
            $registryVersions,
        );
    }
}
