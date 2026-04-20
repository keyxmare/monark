<?php

declare(strict_types=1);

namespace App\Hub\Shared\Domain\Port;

interface LanguagesSummaryInterface
{
    /**
     * Top languages by number of projects using them, ordered from most to least present.
     *
     * @return list<array{name: string, projects_count: int}>
     */
    public function topLanguages(int $limit): array;
}
