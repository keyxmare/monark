<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'catalog_sync_jobs')]
final class SyncJob
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column]
    private int $totalProjects;

    #[ORM\Column]
    private int $completedProjects;

    #[ORM\Column(type: 'string', enumType: SyncJobStatus::class)]
    private SyncJobStatus $status;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $providerId;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $completedAt;

    private function __construct(
        Uuid $id,
        int $totalProjects,
        ?Uuid $providerId,
    ) {
        $this->id = $id;
        $this->totalProjects = $totalProjects;
        $this->completedProjects = 0;
        $this->status = SyncJobStatus::Running;
        $this->providerId = $providerId;
        $this->createdAt = new DateTimeImmutable();
        $this->completedAt = null;
    }

    public static function create(int $totalProjects, ?Uuid $providerId = null): self
    {
        return new self(Uuid::v7(), $totalProjects, $providerId);
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTotalProjects(): int
    {
        return $this->totalProjects;
    }

    public function getCompletedProjects(): int
    {
        return $this->completedProjects;
    }

    public function getStatus(): SyncJobStatus
    {
        return $this->status;
    }

    public function getProviderId(): ?Uuid
    {
        return $this->providerId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function incrementCompleted(): void
    {
        ++$this->completedProjects;

        if ($this->completedProjects >= $this->totalProjects) {
            $this->status = SyncJobStatus::Completed;
            $this->completedAt = new DateTimeImmutable();
        }
    }

    public function markFailed(): void
    {
        $this->status = SyncJobStatus::Failed;
        $this->completedAt = new DateTimeImmutable();
    }
}
