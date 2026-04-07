<?php

declare(strict_types=1);

namespace App\History\Application\Command;

use App\History\Domain\Model\SnapshotSource;
use App\Shared\Domain\DTO\ScanResult;
use DateTimeImmutable;

final readonly class RecordProjectSnapshotCommand
{
    public function __construct(
        public string $projectId,
        public string $commitSha,
        public DateTimeImmutable $snapshotDate,
        public SnapshotSource $source,
        public ScanResult $scanResult,
    ) {
    }
}
