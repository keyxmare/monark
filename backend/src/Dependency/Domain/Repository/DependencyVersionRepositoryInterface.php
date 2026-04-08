<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Repository;

use App\Dependency\Domain\Model\DependencyVersion;
use App\Shared\Domain\ValueObject\PackageManager;

interface DependencyVersionRepositoryInterface
{
    /** @return list<DependencyVersion> */
    public function findByNameAndManager(string $dependencyName, PackageManager $packageManager): array;

    public function findLatestByNameAndManager(string $dependencyName, PackageManager $packageManager): ?DependencyVersion;

    public function findByNameManagerAndVersion(string $dependencyName, PackageManager $packageManager, string $version): ?DependencyVersion;

    public function save(DependencyVersion $version): void;

    public function flush(): void;

    public function clearLatestFlag(string $dependencyName, PackageManager $packageManager): void;
}
