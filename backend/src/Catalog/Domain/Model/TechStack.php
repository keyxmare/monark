<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'catalog_tech_stacks')]
final class TechStack
{
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
}
