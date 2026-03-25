<?php

declare(strict_types=1);

namespace App\Dependency\Infrastructure\Registry;

use App\Dependency\Domain\DTO\RegistryVersion;
use App\Dependency\Domain\Port\PackageRegistryPort;
use App\Shared\Domain\ValueObject\PackageManager;

final readonly class PackageRegistryFactory
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
