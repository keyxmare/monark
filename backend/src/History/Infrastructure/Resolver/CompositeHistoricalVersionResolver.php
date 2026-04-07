<?php

declare(strict_types=1);

namespace App\History\Infrastructure\Resolver;

use App\History\Domain\DTO\ResolvedHistoricalVersion;
use App\History\Domain\Port\HistoricalVersionResolverInterface;
use App\Shared\Domain\ValueObject\PackageManager;
use DateTimeImmutable;

final readonly class CompositeHistoricalVersionResolver implements HistoricalVersionResolverInterface
{
    public function __construct(
        private ProductRegistryHistoricalResolver $registryResolver,
        private EndOfLifeHistoricalResolver $endOfLifeResolver,
    ) {
    }

    public function resolve(string $productName, ?PackageManager $packageManager, DateTimeImmutable $at): ResolvedHistoricalVersion
    {
        $primary = $this->registryResolver->resolve($productName, $packageManager, $at);
        if ($primary->latestVersion !== null && $primary->ltsVersion !== null) {
            return $primary;
        }

        $fallback = $this->endOfLifeResolver->resolve($productName, $packageManager, $at);

        return new ResolvedHistoricalVersion(
            latestVersion: $primary->latestVersion ?? $fallback->latestVersion,
            ltsVersion: $primary->ltsVersion ?? $fallback->ltsVersion,
        );
    }
}
