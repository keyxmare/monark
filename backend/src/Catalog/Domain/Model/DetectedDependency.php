<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Model;

use App\Dependency\Domain\Model\DependencyType;
use App\Dependency\Domain\Model\PackageManager;

final readonly class DetectedDependency
{
    public function __construct(
        public string $name,
        public string $currentVersion,
        public PackageManager $packageManager,
        public DependencyType $type,
    ) {
    }
}
