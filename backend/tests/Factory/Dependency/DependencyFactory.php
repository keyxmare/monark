<?php

declare(strict_types=1);

namespace App\Tests\Factory\Dependency;

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\DependencyType;
use App\Dependency\Domain\Model\PackageManager;
use Tests\Factory\Catalog\ProjectFactory;

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
            project: $overrides['project'] ?? ProjectFactory::create(),
        );
    }
}
