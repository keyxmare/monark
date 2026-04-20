<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Infrastructure\Registry;

use App\Hub\Shared\Domain\ValueObject\PackageManager;
use App\Monitoring\Dependency\Domain\DTO\RegistryVersion;
use App\Monitoring\Dependency\Domain\Port\PackageRegistryPort;
use App\Monitoring\Dependency\Domain\Port\PackageRegistryResolverPort;

final readonly class PackageRegistryFactory implements PackageRegistryResolverPort
{
    /** @param iterable<PackageRegistryPort> $adapters */
    public function __construct(
        private iterable $adapters,
    ) {
    }

    /** @return list<RegistryVersion> */
    public function fetchVersions(string $packageName, PackageManager $manager, ?string $sinceVersion = null): array
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->supports($manager)) {
                return $adapter->fetchVersions($packageName, $manager, $sinceVersion);
            }
        }

        return [];
    }
}
