<?php

declare(strict_types=1);

namespace Tests\Factory\Catalog;

use App\Catalog\Domain\Model\Language;
use App\Catalog\Domain\Model\Project;
use DateTimeImmutable;

final class LanguageFactory
{
    public static function create(
        string $name = 'PHP',
        string $version = '8.4',
        ?DateTimeImmutable $detectedAt = null,
        ?Project $project = null,
    ): Language {
        return Language::create(
            name: $name,
            version: $version,
            detectedAt: $detectedAt ?? new DateTimeImmutable(),
            project: $project ?? ProjectFactory::create(),
        );
    }
}
