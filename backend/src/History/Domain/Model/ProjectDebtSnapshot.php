<?php

declare(strict_types=1);

namespace App\History\Domain\Model;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'history_project_debt_snapshots')]
#[ORM\Index(name: 'idx_debt_snap_project_date', columns: ['project_id', 'snapshot_date'])]
#[ORM\UniqueConstraint(name: 'uniq_debt_snap_commit', columns: ['project_id', 'commit_sha'])]
final class ProjectDebtSnapshot
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'uuid')]
    private Uuid $projectId;

    #[ORM\Column(length: 40)]
    private string $commitSha;

    #[ORM\Column]
    private DateTimeImmutable $snapshotDate;

    #[ORM\Column(type: 'string', length: 20, enumType: SnapshotSource::class)]
    private SnapshotSource $source;

    #[ORM\Column(type: 'integer')]
    private int $totalDeps;

    #[ORM\Column(type: 'integer')]
    private int $outdatedCount;

    #[ORM\Column(type: 'integer')]
    private int $vulnerableCount;

    #[ORM\Column(type: 'integer')]
    private int $majorGapCount;

    #[ORM\Column(type: 'integer')]
    private int $minorGapCount;

    #[ORM\Column(type: 'integer')]
    private int $patchGapCount;

    #[ORM\Column(type: 'integer')]
    private int $ltsGapCount;

    #[ORM\Column(type: 'float')]
    private float $debtScore;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    /** @var Collection<int, DependencySnapshot> */
    #[ORM\OneToMany(targetEntity: DependencySnapshot::class, mappedBy: 'debtSnapshot', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $dependencies;

    private function __construct(
        Uuid $id,
        Uuid $projectId,
        string $commitSha,
        DateTimeImmutable $snapshotDate,
        SnapshotSource $source,
        int $totalDeps,
        int $outdatedCount,
        int $vulnerableCount,
        int $majorGapCount,
        int $minorGapCount,
        int $patchGapCount,
        int $ltsGapCount,
        float $debtScore,
    ) {
        $this->id = $id;
        $this->projectId = $projectId;
        $this->commitSha = $commitSha;
        $this->snapshotDate = $snapshotDate;
        $this->source = $source;
        $this->totalDeps = $totalDeps;
        $this->outdatedCount = $outdatedCount;
        $this->vulnerableCount = $vulnerableCount;
        $this->majorGapCount = $majorGapCount;
        $this->minorGapCount = $minorGapCount;
        $this->patchGapCount = $patchGapCount;
        $this->ltsGapCount = $ltsGapCount;
        $this->debtScore = $debtScore;
        $this->createdAt = new DateTimeImmutable();
        $this->dependencies = new ArrayCollection();
    }

    public static function create(
        Uuid $projectId,
        string $commitSha,
        DateTimeImmutable $snapshotDate,
        SnapshotSource $source,
        int $totalDeps,
        int $outdatedCount,
        int $vulnerableCount,
        int $majorGapCount,
        int $minorGapCount,
        int $patchGapCount,
        int $ltsGapCount,
        float $debtScore,
    ): self {
        return new self(
            id: Uuid::v7(),
            projectId: $projectId,
            commitSha: $commitSha,
            snapshotDate: $snapshotDate,
            source: $source,
            totalDeps: $totalDeps,
            outdatedCount: $outdatedCount,
            vulnerableCount: $vulnerableCount,
            majorGapCount: $majorGapCount,
            minorGapCount: $minorGapCount,
            patchGapCount: $patchGapCount,
            ltsGapCount: $ltsGapCount,
            debtScore: $debtScore,
        );
    }

    public function attachDependency(DependencySnapshot $dependency): void
    {
        $this->dependencies->add($dependency);
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getProjectId(): Uuid
    {
        return $this->projectId;
    }

    public function getCommitSha(): string
    {
        return $this->commitSha;
    }

    public function getSnapshotDate(): DateTimeImmutable
    {
        return $this->snapshotDate;
    }

    public function getSource(): SnapshotSource
    {
        return $this->source;
    }

    public function getTotalDeps(): int
    {
        return $this->totalDeps;
    }

    public function getOutdatedCount(): int
    {
        return $this->outdatedCount;
    }

    public function getVulnerableCount(): int
    {
        return $this->vulnerableCount;
    }

    public function getMajorGapCount(): int
    {
        return $this->majorGapCount;
    }

    public function getMinorGapCount(): int
    {
        return $this->minorGapCount;
    }

    public function getPatchGapCount(): int
    {
        return $this->patchGapCount;
    }

    public function getLtsGapCount(): int
    {
        return $this->ltsGapCount;
    }

    public function getDebtScore(): float
    {
        return $this->debtScore;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, DependencySnapshot> */
    public function getDependencies(): Collection
    {
        return $this->dependencies;
    }
}
