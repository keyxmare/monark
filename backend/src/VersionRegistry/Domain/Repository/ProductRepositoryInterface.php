<?php

declare(strict_types=1);

namespace App\VersionRegistry\Domain\Repository;

use App\Shared\Domain\ValueObject\PackageManager;
use App\VersionRegistry\Domain\Model\Product;

interface ProductRepositoryInterface
{
    public function findByNameAndManager(string $name, ?PackageManager $packageManager): ?Product;

    /** @return list<Product> */
    public function findAll(): array;

    /** @return list<Product> */
    public function findStale(\DateTimeImmutable $before): array;

    /**
     * @param list<string> $names
     * @return list<Product>
     */
    public function findByNames(array $names): array;

    public function save(Product $product): void;
}
