<?php

declare(strict_types=1);

namespace App\Sync\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'global_sync_jobs')]
final class GlobalSyncJob
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'string', enumType: GlobalSyncStatus::class)]
    private GlobalSyncStatus $status;

    #[ORM\Column]
    private int $currentStep;

    #[ORM\Column]
    private string $currentStepName;

    #[ORM\Column]
    private int $stepProgress;

    #[ORM\Column]
    private int $stepTotal;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $completedAt;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $projectId;

    private function __construct(Uuid $id, ?Uuid $projectId = null)
    {
        $this->id = $id;
        $this->projectId = $projectId;
        $this->status = GlobalSyncStatus::Running;
        $this->currentStep = GlobalSyncStep::SyncProjects->value;
        $this->currentStepName = GlobalSyncStep::SyncProjects->name();
        $this->stepProgress = 0;
        $this->stepTotal = 0;
        $this->createdAt = new DateTimeImmutable();
        $this->completedAt = null;
    }

    public static function create(?string $projectId = null): self
    {
        return new self(
            Uuid::v7(),
            $projectId !== null ? Uuid::fromString($projectId) : null,
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getStatus(): GlobalSyncStatus
    {
        return $this->status;
    }

    public function getCurrentStep(): int
    {
        return $this->currentStep;
    }

    public function getCurrentStepName(): string
    {
        return $this->currentStepName;
    }

    public function getStepProgress(): int
    {
        return $this->stepProgress;
    }

    public function getStepTotal(): int
    {
        return $this->stepTotal;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getProjectId(): ?string
    {
        return $this->projectId?->toRfc4122();
    }

    public function startStep(GlobalSyncStep $step, int $total): void
    {
        $this->currentStep = $step->value;
        $this->currentStepName = $step->name();
        $this->stepProgress = 0;
        $this->stepTotal = $total;
    }

    public function incrementProgress(): void
    {
        ++$this->stepProgress;
    }

    public function complete(): void
    {
        $this->status = GlobalSyncStatus::Completed;
        $this->completedAt = new DateTimeImmutable();
    }

    public function markFailed(): void
    {
        $this->status = GlobalSyncStatus::Failed;
        $this->completedAt = new DateTimeImmutable();
    }

    public function isRunning(): bool
    {
        return $this->status === GlobalSyncStatus::Running;
    }

    /** @return list<string> */
    public function getCompletedStepNames(): array
    {
        $completed = [];
        for ($i = 1; $i < $this->currentStep; $i++) {
            $step = GlobalSyncStep::from($i);
            $completed[] = $step->name();
        }

        if ($this->status === GlobalSyncStatus::Completed) {
            $completed[] = $this->currentStepName;
        }

        return $completed;
    }
}
