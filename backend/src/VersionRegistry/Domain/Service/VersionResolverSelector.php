<?php

declare(strict_types=1);

namespace App\VersionRegistry\Domain\Service;

use App\VersionRegistry\Domain\Port\VersionResolverInterface;
use InvalidArgumentException;
use Traversable;

final class VersionResolverSelector
{
    /** @var list<VersionResolverInterface> */
    private array $resolvers;

    /** @param iterable<VersionResolverInterface> $resolvers */
    public function __construct(iterable $resolvers)
    {
        $list = $resolvers instanceof Traversable
            ? \iterator_to_array($resolvers)
            : \array_values((array) $resolvers);

        if ($list === []) {
            throw new InvalidArgumentException('VersionResolverSelector requires at least one resolver.');
        }

        $this->resolvers = \array_values($list);
    }

    public function select(string $resolverSource): ?VersionResolverInterface
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->supports($resolverSource)) {
                return $resolver;
            }
        }

        return null;
    }
}
