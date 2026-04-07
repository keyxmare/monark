<?php

declare(strict_types=1);

namespace App\History\Domain\Model;

use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'history_dependency_snapshots')]
#[ORM\Index(name: 'idx_dep_snap_project_date', columns: ['project_id', 'snapshot_date'])]
#[ORM\Index(name: 'idx_dep_snap_name', columns: ['project_id', 'name', 'snapshot_date'])]
final class DependencySnapshot
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: ProjectDebtSnapshot::class, inversedBy: 'dependencies')]
    #[ORM\JoinColumn(name: 'debt_snapshot_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ProjectDebtSnapshot $debtSnapshot;

    #[ORM\Column(type: 'uuid')]
    private Uuid $projectId;

    #[ORM\Column]
    private DateTimeImmutable $snapshotDate;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', enumType: PackageManager::class)]
    private PackageManager $packageManager;

    #[ORM\Column(type: 'string', enumType: DependencyType::class)]
    private DependencyType $type;

    #[ORM\Column(length: 100)]
    private string $currentVersion;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $latestVersionAtDate;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ltsVersionAtDate;

    #[ORM\Column]
    private bool $isOutdated;

    #[ORM\Column(type: 'string', length: 20, enumType: GapType::class)]
    private GapType $gapType;

    public function __construct(
        ProjectDebtSnapshot $debtSnapshot,
        string $name,
        PackageManager $packageManager,
        DependencyType $type,
        string $currentVersion,
        ?string $latestVersionAtDate,
        ?string $ltsVersionAtDate,
        bool $isOutdated,
        GapType $gapType,
    ) {
        $this->id = Uuid::v7();
        $this->debtSnapshot = $debtSnapshot;
        $this->projectId = $debtSnapshot->getProjectId();
        $this->snapshotDate = $debtSnapshot->getSnapshotDate();
        $this->name = $name;
        $this->packageManager = $packageManager;
        $this->type = $type;
        $this->currentVersion = $currentVersion;
        $this->latestVersionAtDate = $latestVersionAtDate;
        $this->ltsVersionAtDate = $ltsVersionAtDate;
        $this->isOutdated = $isOutdated;
        $this->gapType = $gapType;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getDebtSnapshot(): ProjectDebtSnapshot
    {
        return $this->debtSnapshot;
    }

    public function getProjectId(): Uuid
    {
        return $this->projectId;
    }

    public function getSnapshotDate(): DateTimeImmutable
    {
        return $this->snapshotDate;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPackageManager(): PackageManager
    {
        return $this->packageManager;
    }

    public function getType(): DependencyType
    {
        return $this->type;
    }

    public function getCurrentVersion(): string
    {
        return $this->currentVersion;
    }

    public function getLatestVersionAtDate(): ?string
    {
        return $this->latestVersionAtDate;
    }

    public function getLtsVersionAtDate(): ?string
    {
        return $this->ltsVersionAtDate;
    }

    public function isOutdated(): bool
    {
        return $this->isOutdated;
    }

    public function getGapType(): GapType
    {
        return $this->gapType;
    }
}
