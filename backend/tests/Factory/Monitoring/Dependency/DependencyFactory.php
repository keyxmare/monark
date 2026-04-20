<?php

declare(strict_types=1);

namespace App\Tests\Factory\Monitoring\Dependency;

use App\Hub\Shared\Domain\ValueObject\DependencyType;
use App\Hub\Shared\Domain\ValueObject\PackageManager;
use App\Monitoring\Dependency\Domain\Model\Dependency;
use Symfony\Component\Uid\Uuid;

final class DependencyFactory
{
    public static function create(array $overrides = []): Dependency
    {
        return Dependency::create(
            name: $overrides['name'] ?? 'symfony/framework-bundle',
            currentVersion: $overrides['currentVersion'] ?? '7.2.0',
            latestVersion: $overrides['latestVersion'] ?? '8.0.0',
            ltsVersion: $overrides['ltsVersion'] ?? '7.4.0',
            packageManager: $overrides['packageManager'] ?? PackageManager::Composer,
            type: $overrides['type'] ?? DependencyType::Runtime,
            isOutdated: $overrides['isOutdated'] ?? true,
            projectId: $overrides['projectId'] ?? Uuid::v7(),
        );
    }
}
