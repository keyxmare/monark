<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Model;

use App\Shared\Domain\ValueObject\PackageManager;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'dependency_versions')]
#[ORM\UniqueConstraint(name: 'uniq_dep_version', columns: ['dependency_name', 'package_manager', 'version'])]
#[ORM\Index(name: 'idx_dep_version_lookup', columns: ['dependency_name', 'package_manager'])]
class DependencyVersion
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $dependencyName;

    #[ORM\Column(type: 'string', enumType: PackageManager::class)]
    private PackageManager $packageManager;

    #[ORM\Column(length: 100)]
    private string $version;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $releaseDate;

    #[ORM\Column]
    private bool $isLts;

    #[ORM\Column]
    private bool $isLatest;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    private function __construct(
        Uuid $id,
        string $dependencyName,
        PackageManager $packageManager,
        string $version,
        ?DateTimeImmutable $releaseDate,
        bool $isLts,
        bool $isLatest,
    ) {
        $this->id = $id;
        $this->dependencyName = $dependencyName;
        $this->packageManager = $packageManager;
        $this->version = $version;
        $this->releaseDate = $releaseDate;
        $this->isLts = $isLts;
        $this->isLatest = $isLatest;
        $this->createdAt = new DateTimeImmutable();
    }

    public static function create(
        string $dependencyName,
        PackageManager $packageManager,
        string $version,
        ?DateTimeImmutable $releaseDate = null,
        bool $isLts = false,
        bool $isLatest = false,
    ): self {
        return new self(
            id: Uuid::v7(),
            dependencyName: $dependencyName,
            packageManager: $packageManager,
            version: $version,
            releaseDate: $releaseDate,
            isLts: $isLts,
            isLatest: $isLatest,
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getDependencyName(): string
    {
        return $this->dependencyName;
    }

    public function getPackageManager(): PackageManager
    {
        return $this->packageManager;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getReleaseDate(): ?DateTimeImmutable
    {
        return $this->releaseDate;
    }

    public function isLts(): bool
    {
        return $this->isLts;
    }

    public function isLatest(): bool
    {
        return $this->isLatest;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function markAsLatest(bool $isLatest): void
    {
        $this->isLatest = $isLatest;
    }
}
