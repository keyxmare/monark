<?php

declare(strict_types=1);

namespace App\Coverage\Infrastructure\Provider;

use App\Catalog\Domain\Model\ProviderType;
use App\Coverage\Domain\Port\CoverageProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class CoverageProviderRegistry
{
    /** @param iterable<CoverageProviderInterface> $providers */
    public function __construct(
        #[AutowireIterator('app.coverage_provider')]
        private iterable $providers,
    ) {
    }

    public function resolve(ProviderType $type): ?CoverageProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($type)) {
                return $provider;
            }
        }

        return null;
    }
}
