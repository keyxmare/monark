<?php

declare(strict_types=1);

namespace App\History\Infrastructure\Resolver;

use App\History\Domain\DTO\ResolvedHistoricalVersion;
use App\History\Domain\Port\HistoricalVersionResolverInterface;
use App\Shared\Domain\ValueObject\PackageManager;
use App\VersionRegistry\Domain\Repository\ProductVersionRepositoryInterface;
use DateTimeImmutable;

final readonly class ProductRegistryHistoricalResolver implements HistoricalVersionResolverInterface
{
    public function __construct(
        private ProductVersionRepositoryInterface $productVersionRepository,
    ) {
    }

    public function resolve(string $productName, ?PackageManager $packageManager, DateTimeImmutable $at): ResolvedHistoricalVersion
    {
        $latest = $this->productVersionRepository->findLatestReleasedBefore($productName, $packageManager, $at);
        $lts = $this->productVersionRepository->findLatestLtsBefore($productName, $packageManager, $at);

        return new ResolvedHistoricalVersion(
            latestVersion: $latest?->getVersion(),
            ltsVersion: $lts?->getVersion(),
        );
    }
}
