<?php

declare(strict_types=1);

namespace App\VersionRegistry\Domain\Model;

use App\Shared\Domain\ValueObject\PackageManager;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'products')]
#[ORM\UniqueConstraint(name: 'uniq_product', columns: ['name', 'package_manager'])]
final class Product
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', enumType: ProductType::class)]
    private ProductType $type;

    #[ORM\Column(type: 'string', nullable: true, enumType: PackageManager::class)]
    private ?PackageManager $packageManager;

    #[ORM\Column(type: 'string', enumType: ResolverSource::class)]
    private ResolverSource $resolverSource;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $latestVersion;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ltsVersion;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $lastSyncedAt;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    private function __construct(
        Uuid $id,
        string $name,
        ProductType $type,
        ResolverSource $resolverSource,
        ?PackageManager $packageManager,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
        $this->resolverSource = $resolverSource;
        $this->packageManager = $packageManager;
        $this->latestVersion = null;
        $this->ltsVersion = null;
        $this->lastSyncedAt = null;
        $this->createdAt = new DateTimeImmutable();
    }

    public static function create(
        string $name,
        ProductType $type,
        ResolverSource $resolverSource,
        ?PackageManager $packageManager = null,
    ): self {
        return new self(
            id: Uuid::v7(),
            name: $name,
            type: $type,
            resolverSource: $resolverSource,
            packageManager: $packageManager,
        );
    }

    public function updateSyncResult(?string $latestVersion, ?string $ltsVersion): void
    {
        $this->latestVersion = $latestVersion;
        $this->ltsVersion = $ltsVersion;
        $this->lastSyncedAt = new DateTimeImmutable();
    }

    public function getId(): Uuid { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getType(): ProductType { return $this->type; }
    public function getPackageManager(): ?PackageManager { return $this->packageManager; }
    public function getResolverSource(): ResolverSource { return $this->resolverSource; }
    public function getLatestVersion(): ?string { return $this->latestVersion; }
    public function getLtsVersion(): ?string { return $this->ltsVersion; }
    public function getLastSyncedAt(): ?DateTimeImmutable { return $this->lastSyncedAt; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
}
