<?php

declare(strict_types=1);

namespace App\Hub\Shared\Domain\Port;

interface ProvidersBreakdownInterface
{
    /**
     * Breakdown of configured providers grouped by type, ordered by project count descending.
     *
     * @return list<array{type: string, label: string, connected: bool, projects_count: int}>
     */
    public function byType(): array;
}
