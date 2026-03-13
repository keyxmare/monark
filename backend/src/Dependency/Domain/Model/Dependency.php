<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Model;

use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'dependencies')]
final class Dependency
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 100)]
    private string $currentVersion;

    #[ORM\Column(length: 100)]
    private string $latestVersion;

    #[ORM\Column(length: 100)]
    private string $ltsVersion;

    #[ORM\Column(type: 'string', enumType: PackageManager::class)]
    private PackageManager $packageManager;

    #[ORM\Column(type: 'string', enumType: DependencyType::class)]
    private DependencyType $type;

    #[ORM\Column]
    private bool $isOutdated;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $repositoryUrl;

    #[ORM\Column(type: 'uuid')]
    private Uuid $projectId;

    /** @var Collection<int, Vulnerability> */
    #[ORM\OneToMany(targetEntity: Vulnerability::class, mappedBy: 'dependency', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $vulnerabilities;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    private function __construct(
        Uuid $id,
        string $name,
        string $currentVersion,
        string $latestVersion,
        string $ltsVersion,
        PackageManager $packageManager,
        DependencyType $type,
        bool $isOutdated,
        Uuid $projectId,
        ?string $repositoryUrl = null,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->currentVersion = $currentVersion;
        $this->latestVersion = $latestVersion;
        $this->ltsVersion = $ltsVersion;
        $this->packageManager = $packageManager;
        $this->type = $type;
        $this->isOutdated = $isOutdated;
        $this->projectId = $projectId;
        $this->repositoryUrl = $repositoryUrl;
        $this->vulnerabilities = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public static function create(
        string $name,
        string $currentVersion,
        string $latestVersion,
        string $ltsVersion,
        PackageManager $packageManager,
        DependencyType $type,
        bool $isOutdated,
        Uuid $projectId,
        ?string $repositoryUrl = null,
    ): self {
        return new self(
            id: Uuid::v7(),
            name: $name,
            currentVersion: $currentVersion,
            latestVersion: $latestVersion,
            ltsVersion: $ltsVersion,
            packageManager: $packageManager,
            type: $type,
            isOutdated: $isOutdated,
            projectId: $projectId,
            repositoryUrl: $repositoryUrl,
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

    public function getCurrentVersion(): string
    {
        return $this->currentVersion;
    }

    public function getLatestVersion(): string
    {
        return $this->latestVersion;
    }

    public function getLtsVersion(): string
    {
        return $this->ltsVersion;
    }

    public function getPackageManager(): PackageManager
    {
        return $this->packageManager;
    }

    public function getType(): DependencyType
    {
        return $this->type;
    }

    public function isOutdated(): bool
    {
        return $this->isOutdated;
    }

    public function getRepositoryUrl(): ?string
    {
        return $this->repositoryUrl;
    }

    public function getProjectId(): Uuid
    {
        return $this->projectId;
    }

    /** @return Collection<int, Vulnerability> */
    public function getVulnerabilities(): Collection
    {
        return $this->vulnerabilities;
    }

    public function getVulnerabilityCount(): int
    {
        return $this->vulnerabilities->count();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function update(
        ?string $name = null,
        ?string $currentVersion = null,
        ?string $latestVersion = null,
        ?string $ltsVersion = null,
        ?PackageManager $packageManager = null,
        ?DependencyType $type = null,
        ?bool $isOutdated = null,
        ?string $repositoryUrl = null,
        bool $clearRepositoryUrl = false,
    ): void {
        if ($name !== null) {
            $this->name = $name;
        }
        if ($currentVersion !== null) {
            $this->currentVersion = $currentVersion;
        }
        if ($latestVersion !== null) {
            $this->latestVersion = $latestVersion;
        }
        if ($ltsVersion !== null) {
            $this->ltsVersion = $ltsVersion;
        }
        if ($packageManager !== null) {
            $this->packageManager = $packageManager;
        }
        if ($type !== null) {
            $this->type = $type;
        }
        if ($isOutdated !== null) {
            $this->isOutdated = $isOutdated;
        }
        if ($repositoryUrl !== null) {
            $this->repositoryUrl = $repositoryUrl;
        } elseif ($clearRepositoryUrl) {
            $this->repositoryUrl = null;
        }
        $this->updatedAt = new DateTimeImmutable();
    }
}
