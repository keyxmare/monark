<?php

declare(strict_types=1);

namespace Tests\Factory\Catalog;

use App\Catalog\Domain\Model\Framework;
use App\Catalog\Domain\Model\Language;
use App\Catalog\Domain\Model\Project;
use DateTimeImmutable;

final class FrameworkFactory
{
    public static function create(
        string $name = 'Symfony',
        string $version = '7.1',
        ?DateTimeImmutable $detectedAt = null,
        ?Language $language = null,
        ?Project $project = null,
    ): Framework {
        $project = $project ?? ProjectFactory::create();

        return Framework::create(
            name: $name,
            version: $version,
            detectedAt: $detectedAt ?? new DateTimeImmutable(),
            language: $language ?? LanguageFactory::create(project: $project),
            project: $project,
        );
    }
}
