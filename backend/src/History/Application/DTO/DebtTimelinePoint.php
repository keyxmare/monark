<?php

declare(strict_types=1);

namespace App\History\Application\DTO;

use App\History\Domain\Model\ProjectDebtSnapshot;

final readonly class DebtTimelinePoint
{
    public function __construct(
        public string $snapshotId,
        public string $commitSha,
        public string $snapshotDate,
        public string $source,
        public int $totalDeps,
        public int $outdatedCount,
        public int $vulnerableCount,
        public int $majorGapCount,
        public int $minorGapCount,
        public int $patchGapCount,
        public int $ltsGapCount,
        public float $debtScore,
    ) {
    }

    public static function fromEntity(ProjectDebtSnapshot $snapshot): self
    {
        return new self(
            snapshotId: $snapshot->getId()->toRfc4122(),
            commitSha: $snapshot->getCommitSha(),
            snapshotDate: $snapshot->getSnapshotDate()->format(\DATE_ATOM),
            source: $snapshot->getSource()->value,
            totalDeps: $snapshot->getTotalDeps(),
            outdatedCount: $snapshot->getOutdatedCount(),
            vulnerableCount: $snapshot->getVulnerableCount(),
            majorGapCount: $snapshot->getMajorGapCount(),
            minorGapCount: $snapshot->getMinorGapCount(),
            patchGapCount: $snapshot->getPatchGapCount(),
            ltsGapCount: $snapshot->getLtsGapCount(),
            debtScore: $snapshot->getDebtScore(),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'snapshotId' => $this->snapshotId,
            'commitSha' => $this->commitSha,
            'snapshotDate' => $this->snapshotDate,
            'source' => $this->source,
            'totalDeps' => $this->totalDeps,
            'outdatedCount' => $this->outdatedCount,
            'vulnerableCount' => $this->vulnerableCount,
            'majorGapCount' => $this->majorGapCount,
            'minorGapCount' => $this->minorGapCount,
            'patchGapCount' => $this->patchGapCount,
            'ltsGapCount' => $this->ltsGapCount,
            'debtScore' => $this->debtScore,
        ];
    }
}
