<?php

declare(strict_types=1);

namespace Tests\Factory\Catalog;

use App\Catalog\Domain\Model\Framework;
use App\Catalog\Domain\Model\Project;
use DateTimeImmutable;

final class FrameworkFactory
{
    public static function create(
        string $name = 'Symfony',
        string $version = '7.1',
        ?DateTimeImmutable $detectedAt = null,
        string $languageName = 'PHP',
        string $languageVersion = '8.4',
        ?Project $project = null,
    ): Framework {
        return Framework::create(
            name: $name,
            version: $version,
            detectedAt: $detectedAt ?? new DateTimeImmutable(),
            languageName: $languageName,
            languageVersion: $languageVersion,
            project: $project ?? ProjectFactory::create(),
        );
    }
}
