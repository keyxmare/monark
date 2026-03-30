<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Model;

use App\Catalog\Domain\Event\TechStackVersionStatusUpdated;
use App\Shared\Domain\Model\RecordsDomainEvents;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'catalog_tech_stacks')]
final class TechStack
{
    use RecordsDomainEvents;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 100)]
    private string $language;

    #[ORM\Column(length: 100)]
    private string $framework;

    #[ORM\Column(length: 50)]
    private string $version;

    #[ORM\Column(length: 50)]
    private string $frameworkVersion;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $detectedAt;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'techStacks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $latestLts = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ltsGap = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $maintenanceStatus = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?DateTimeImmutable $eolDate = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $versionSyncedAt = null;

    private function __construct(
        Uuid $id,
        string $language,
        string $framework,
        string $version,
        string $frameworkVersion,
        DateTimeImmutable $detectedAt,
        Project $project,
    ) {
        $this->id = $id;
        $this->language = $language;
        $this->framework = $framework;
        $this->version = $version;
        $this->frameworkVersion = $frameworkVersion;
        $this->detectedAt = $detectedAt;
        $this->project = $project;
        $this->createdAt = new DateTimeImmutable();
    }

    public static function create(
        string $language,
        string $framework,
        string $version,
        string $frameworkVersion,
        DateTimeImmutable $detectedAt,
        Project $project,
    ): self {
        return new self(
            id: Uuid::v7(),
            language: $language,
            framework: $framework,
            version: $version,
            frameworkVersion: $frameworkVersion,
            detectedAt: $detectedAt,
            project: $project,
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getFramework(): string
    {
        return $this->framework;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getFrameworkVersion(): string
    {
        return $this->frameworkVersion;
    }

    public function getDetectedAt(): DateTimeImmutable
    {
        return $this->detectedAt;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updateVersionStatus(
        ?string $latestLts,
        ?string $ltsGap,
        ?string $maintenanceStatus,
        ?DateTimeImmutable $eolDate,
    ): void {
        $this->latestLts = $latestLts;
        $this->ltsGap = $ltsGap;
        $this->maintenanceStatus = $maintenanceStatus;
        $this->eolDate = $eolDate;
        $this->versionSyncedAt = new DateTimeImmutable();

        $this->recordEvent(new TechStackVersionStatusUpdated(
            techStackId: $this->id->toRfc4122(),
            projectId: $this->project->getId()->toRfc4122(),
            framework: $this->framework,
            latestLts: $latestLts,
            maintenanceStatus: $maintenanceStatus,
        ));
    }

    public function getLatestLts(): ?string
    {
        return $this->latestLts;
    }

    public function getLtsGap(): ?string
    {
        return $this->ltsGap;
    }

    public function getMaintenanceStatus(): ?string
    {
        return $this->maintenanceStatus;
    }

    public function getEolDate(): ?DateTimeImmutable
    {
        return $this->eolDate;
    }

    public function getVersionSyncedAt(): ?DateTimeImmutable
    {
        return $this->versionSyncedAt;
    }
}
