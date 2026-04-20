<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Domain\Port;

use App\Hub\Shared\Domain\ValueObject\PackageManager;
use App\Monitoring\Dependency\Domain\DTO\RegistryVersion;

interface PackageRegistryPort
{
    public function supports(PackageManager $manager): bool;

    /** @return list<RegistryVersion> */
    public function fetchVersions(string $packageName, PackageManager $manager, ?string $sinceVersion = null): array;
}
