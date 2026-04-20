<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Domain\Repository;

use App\Hub\Shared\Domain\ValueObject\PackageManager;
use App\Monitoring\Dependency\Domain\Model\DependencyVersion;

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
