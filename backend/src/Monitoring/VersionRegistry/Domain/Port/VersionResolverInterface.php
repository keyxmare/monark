<?php

declare(strict_types=1);

namespace App\Monitoring\VersionRegistry\Domain\Port;

use App\Monitoring\VersionRegistry\Domain\DTO\ResolvedVersion;
use DateTimeImmutable;

interface VersionResolverInterface
{
    public function supports(string $resolverSource): bool;

    /** @return list<ResolvedVersion> */
    public function fetchVersions(string $productName, ?DateTimeImmutable $since = null): array;
}
