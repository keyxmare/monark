<?php

declare(strict_types=1);

namespace App\Monitoring\VersionRegistry\Domain\Repository;

use App\Hub\Shared\Domain\ValueObject\PackageManager;
use App\Monitoring\VersionRegistry\Domain\Model\ProductVersion;

interface ProductVersionRepositoryInterface
{
    /** @return list<ProductVersion> */
    public function findByNameAndManager(string $productName, ?PackageManager $packageManager): array;

    public function findLatestByNameAndManager(string $productName, ?PackageManager $packageManager): ?ProductVersion;

    public function findByNameManagerAndVersion(string $productName, ?PackageManager $packageManager, string $version): ?ProductVersion;

    public function save(ProductVersion $version): void;

    public function persist(ProductVersion $version): void;

    public function flush(): void;

    public function clearLatestFlag(string $productName, ?PackageManager $packageManager): void;
}
