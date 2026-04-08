<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Port;

use App\Dependency\Domain\DTO\RegistryVersion;
use App\Shared\Domain\ValueObject\PackageManager;

interface PackageRegistryResolverPort
{
    /** @return list<RegistryVersion> */
    public function fetchVersions(string $packageName, PackageManager $manager, ?string $sinceVersion = null): array;
}
