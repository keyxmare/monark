<?php

declare(strict_types=1);

namespace App\VersionRegistry\Domain\Port;

use App\Shared\Domain\ValueObject\PackageManager;
use App\VersionRegistry\Domain\DTO\ResolvedVersion;
use DateTimeImmutable;

interface PackageManagerAwareVersionResolverInterface extends VersionResolverInterface
{
    /** @return list<ResolvedVersion> */
    public function fetchVersions(string $productName, ?DateTimeImmutable $since = null, ?PackageManager $packageManager = null): array;
}
