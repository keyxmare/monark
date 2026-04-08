<?php

declare(strict_types=1);

namespace App\Shared\Domain\DTO;

use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;

final readonly class DetectedDependency
{
    public function __construct(
        public string $name,
        public string $currentVersion,
        public PackageManager $packageManager,
        public DependencyType $type,
        public ?string $repositoryUrl = null,
    ) {
    }
}
