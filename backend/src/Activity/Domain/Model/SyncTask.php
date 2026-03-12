<?php

declare(strict_types=1);

namespace App\Activity\Domain\Model;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'sync_tasks')]
#[ORM\Index(columns: ['project_id', 'type', 'status'], name: 'idx_sync_task_project_type_status')]
final class SyncTask
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'string', enumType: SyncTaskType::class)]
    private SyncTaskType $type;

    #[ORM\Column(type: 'string', enumType: SyncTaskSeverity::class)]
    private SyncTaskSeverity $severity;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'string', enumType: SyncTaskStatus::class)]
    private SyncTaskStatus $status;

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    private array $metadata;

    #[ORM\Column(type: 'uuid')]
    private Uuid $projectId;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $resolvedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    private function __construct(
        Uuid $id,
        SyncTaskType $type,
        SyncTaskSeverity $severity,
        string $title,
        string $description,
        SyncTaskStatus $status,
        array $metadata,
        Uuid $projectId,
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->severity = $severity;
        $this->title = $title;
        $this->description = $description;
        $this->status = $status;
        $this->metadata = $metadata;
        $this->projectId = $projectId;
        $this->resolvedAt = null;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    /** @param array<string, mixed> $metadata */
    public static function create(
        SyncTaskType $type,
        SyncTaskSeverity $severity,
        string $title,
        string $description,
        array $metadata,
        Uuid $projectId,
    ): self {
        return new self(
            id: Uuid::v7(),
            type: $type,
            severity: $severity,
            title: $title,
            description: $description,
            status: SyncTaskStatus::Open,
            metadata: $metadata,
            projectId: $projectId,
        );
    }

    public function updateInfo(
        SyncTaskSeverity $severity,
        string $title,
        string $description,
        array $metadata,
    ): void {
        $this->severity = $severity;
        $this->title = $title;
        $this->description = $description;
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function changeStatus(SyncTaskStatus $status): void
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();

        if ($status === SyncTaskStatus::Resolved || $status === SyncTaskStatus::Dismissed) {
            $this->resolvedAt = new \DateTimeImmutable();
        }
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getType(): SyncTaskType
    {
        return $this->type;
    }

    public function getSeverity(): SyncTaskSeverity
    {
        return $this->severity;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getStatus(): SyncTaskStatus
    {
        return $this->status;
    }

    /** @return array<string, mixed> */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getProjectId(): Uuid
    {
        return $this->projectId;
    }

    public function getResolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isOpen(): bool
    {
        return $this->status === SyncTaskStatus::Open || $this->status === SyncTaskStatus::Acknowledged;
    }

    public function getMetadataKey(): string
    {
        return match ($this->type) {
            SyncTaskType::OutdatedDependency, SyncTaskType::NewDependency => $this->metadata['dependencyName'] ?? '',
            SyncTaskType::Vulnerability => $this->metadata['cveId'] ?? '',
            SyncTaskType::StackUpgrade => ($this->metadata['language'] ?? '') . ':' . ($this->metadata['framework'] ?? ''),
            SyncTaskType::StalePr => $this->metadata['externalId'] ?? '',
        };
    }
}
