<?php

declare(strict_types=1);

namespace App\Dependency\Infrastructure\Registry\Attribute;

use App\Shared\Domain\ValueObject\PackageManager;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsPackageRegistry
{
    public function __construct(
        public PackageManager $manager,
    ) {
    }
}
