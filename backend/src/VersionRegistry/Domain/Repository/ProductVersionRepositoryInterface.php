<?php

declare(strict_types=1);

namespace App\VersionRegistry\Domain\Repository;

use App\Shared\Domain\ValueObject\PackageManager;
use App\VersionRegistry\Domain\Model\ProductVersion;

interface ProductVersionRepositoryInterface
{
    /** @return list<ProductVersion> */
    public function findByNameAndManager(string $productName, ?PackageManager $packageManager): array;

    public function findLatestByNameAndManager(string $productName, ?PackageManager $packageManager): ?ProductVersion;

    public function findByNameManagerAndVersion(string $productName, ?PackageManager $packageManager, string $version): ?ProductVersion;

    /** @return array<string, true> version string => true */
    public function findKnownVersionStrings(string $productName, ?PackageManager $packageManager): array;

    public function save(ProductVersion $version): void;

    public function saveMany(ProductVersion ...$versions): void;

    public function clearLatestFlag(string $productName, ?PackageManager $packageManager): void;
}
