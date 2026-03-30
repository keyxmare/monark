<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Model;

use App\Shared\Domain\Model\RecordsDomainEvents;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'catalog_languages')]
#[ORM\HasLifecycleCallbacks]
final class Language
{
    use RecordsDomainEvents;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 50)]
    private string $version;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $detectedAt;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?DateTimeImmutable $eolDate = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $maintenanceStatus = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    private function __construct(
        Uuid $id,
        string $name,
        string $version,
        DateTimeImmutable $detectedAt,
        Project $project,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->version = $version;
        $this->detectedAt = $detectedAt;
        $this->project = $project;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public static function create(
        string $name,
        string $version,
        DateTimeImmutable $detectedAt,
        Project $project,
    ): self {
        return new self(
            id: Uuid::v7(),
            name: $name,
            version: $version,
            detectedAt: $detectedAt,
            project: $project,
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getVersion(): string
    {
        return $this->version;
    }
    public function getDetectedAt(): DateTimeImmutable
    {
        return $this->detectedAt;
    }
    public function getEolDate(): ?DateTimeImmutable
    {
        return $this->eolDate;
    }
    public function getMaintenanceStatus(): ?string
    {
        return $this->maintenanceStatus;
    }
    public function getProject(): Project
    {
        return $this->project;
    }
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function updateStatus(?string $maintenanceStatus, ?DateTimeImmutable $eolDate): void
    {
        $this->maintenanceStatus = $maintenanceStatus;
        $this->eolDate = $eolDate;
        $this->updatedAt = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
