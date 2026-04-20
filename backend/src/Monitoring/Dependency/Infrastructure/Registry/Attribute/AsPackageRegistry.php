<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Infrastructure\Registry\Attribute;

use App\Hub\Shared\Domain\ValueObject\PackageManager;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsPackageRegistry
{
    public function __construct(
        public PackageManager $manager,
    ) {
    }
}
