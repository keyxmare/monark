<?php

declare(strict_types=1);

namespace App\History\Domain\Port;

use App\History\Domain\DTO\ResolvedHistoricalVersion;
use App\Shared\Domain\ValueObject\PackageManager;
use DateTimeImmutable;

interface HistoricalVersionResolverInterface
{
    public function resolve(string $productName, ?PackageManager $packageManager, DateTimeImmutable $at): ResolvedHistoricalVersion;
}
