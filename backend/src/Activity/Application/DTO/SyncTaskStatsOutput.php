<?php

declare(strict_types=1);

namespace App\Activity\Application\DTO;

final readonly class SyncTaskStatsOutput
{
    /**
     * @param list<array{label: string, count: int}> $byType
     * @param list<array{label: string, count: int}> $bySeverity
     * @param list<array{label: string, count: int}> $byStatus
     */
    public function __construct(
        public array $byType,
        public array $bySeverity,
        public array $byStatus,
    ) {
    }

    /** @return array{by_type: list<array{label: string, count: int}>, by_severity: list<array{label: string, count: int}>, by_status: list<array{label: string, count: int}>} */
    public function toArray(): array
    {
        return [
            'by_type' => $this->byType,
            'by_severity' => $this->bySeverity,
            'by_status' => $this->byStatus,
        ];
    }
}
