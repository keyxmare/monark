<?php

declare(strict_types=1);

namespace App\Monitoring\VersionRegistry\Domain\Port;

use App\Hub\Shared\Domain\ValueObject\PackageManager;
use App\Monitoring\VersionRegistry\Domain\DTO\ResolvedVersion;
use DateTimeImmutable;

interface PackageManagerAwareVersionResolverInterface extends VersionResolverInterface
{
    /** @return list<ResolvedVersion> */
    public function fetchVersions(string $productName, ?DateTimeImmutable $since = null, ?PackageManager $packageManager = null): array;
}
