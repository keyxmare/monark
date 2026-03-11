<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Model;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'catalog_pipelines')]
final class Pipeline
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $externalId;

    #[ORM\Column(length: 255)]
    private string $ref;

    #[ORM\Column(type: 'string', enumType: PipelineStatus::class)]
    private PipelineStatus $status;

    #[ORM\Column(type: 'integer')]
    private int $duration;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $finishedAt;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'pipelines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    private function __construct(
        Uuid $id,
        string $externalId,
        string $ref,
        PipelineStatus $status,
        int $duration,
        \DateTimeImmutable $startedAt,
        ?\DateTimeImmutable $finishedAt,
        Project $project,
    ) {
        $this->id = $id;
        $this->externalId = $externalId;
        $this->ref = $ref;
        $this->status = $status;
        $this->duration = $duration;
        $this->startedAt = $startedAt;
        $this->finishedAt = $finishedAt;
        $this->project = $project;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function create(
        string $externalId,
        string $ref,
        PipelineStatus $status,
        int $duration,
        \DateTimeImmutable $startedAt,
        ?\DateTimeImmutable $finishedAt,
        Project $project,
    ): self {
        return new self(
            id: Uuid::v7(),
            externalId: $externalId,
            ref: $ref,
            status: $status,
            duration: $duration,
            startedAt: $startedAt,
            finishedAt: $finishedAt,
            project: $project,
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function getRef(): string
    {
        return $this->ref;
    }

    public function getStatus(): PipelineStatus
    {
        return $this->status;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
