<?php

declare(strict_types=1);

namespace Tests\Factory\Catalog;

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\TechStack;

final class TechStackFactory
{
    public static function create(
        string $language = 'PHP',
        string $framework = 'Symfony',
        string $version = '8.4',
        string $frameworkVersion = '8.0',
        ?\DateTimeImmutable $detectedAt = null,
        ?Project $project = null,
    ): TechStack {
        return TechStack::create(
            language: $language,
            framework: $framework,
            version: $version,
            frameworkVersion: $frameworkVersion,
            detectedAt: $detectedAt ?? new \DateTimeImmutable(),
            project: $project ?? ProjectFactory::create(),
        );
    }
}
