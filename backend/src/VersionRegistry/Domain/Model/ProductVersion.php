<?php

declare(strict_types=1);

namespace App\VersionRegistry\Domain\Model;

use App\Shared\Domain\ValueObject\PackageManager;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'product_versions')]
#[ORM\UniqueConstraint(name: 'uniq_product_version', columns: ['product_name', 'package_manager', 'version'])]
#[ORM\Index(name: 'idx_product_version_lookup', columns: ['product_name', 'package_manager'])]
#[ORM\Index(name: 'idx_product_version_latest', columns: ['product_name', 'package_manager', 'is_latest'])]
class ProductVersion
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $productName;

    #[ORM\Column(type: 'string', nullable: true, enumType: PackageManager::class)]
    private ?PackageManager $packageManager;

    #[ORM\Column(length: 100)]
    private string $version;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $releaseDate;

    #[ORM\Column]
    private bool $isLts;

    #[ORM\Column]
    private bool $isLatest;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $eolDate;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    private function __construct(
        Uuid $id,
        string $productName,
        ?PackageManager $packageManager,
        string $version,
        ?DateTimeImmutable $releaseDate,
        bool $isLts,
        bool $isLatest,
        ?string $eolDate,
    ) {
        $this->id = $id;
        $this->productName = $productName;
        $this->packageManager = $packageManager;
        $this->version = $version;
        $this->releaseDate = $releaseDate;
        $this->isLts = $isLts;
        $this->isLatest = $isLatest;
        $this->eolDate = $eolDate;
        $this->createdAt = new DateTimeImmutable();
    }

    public static function create(
        string $productName,
        string $version,
        ?PackageManager $packageManager = null,
        ?DateTimeImmutable $releaseDate = null,
        bool $isLts = false,
        bool $isLatest = false,
        ?string $eolDate = null,
    ): self {
        return new self(
            id: Uuid::v7(),
            productName: $productName,
            packageManager: $packageManager,
            version: $version,
            releaseDate: $releaseDate,
            isLts: $isLts,
            isLatest: $isLatest,
            eolDate: $eolDate,
        );
    }

    public function markAsLatest(bool $isLatest): void { $this->isLatest = $isLatest; }

    public function getId(): Uuid { return $this->id; }
    public function getProductName(): string { return $this->productName; }
    public function getPackageManager(): ?PackageManager { return $this->packageManager; }
    public function getVersion(): string { return $this->version; }
    public function getReleaseDate(): ?DateTimeImmutable { return $this->releaseDate; }
    public function isLts(): bool { return $this->isLts; }
    public function isLatest(): bool { return $this->isLatest; }
    public function getEolDate(): ?string { return $this->eolDate; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
}
