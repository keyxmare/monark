<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Model;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'catalog_providers')]
final class Provider
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', enumType: ProviderType::class)]
    private ProviderType $type;

    #[ORM\Column(length: 500)]
    private string $url;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $apiToken;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $username;

    #[ORM\Column(type: 'string', enumType: ProviderStatus::class)]
    private ProviderStatus $status;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $lastSyncAt;

    /** @var Collection<int, Project> */
    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'provider')]
    private Collection $projects;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    private function __construct(
        Uuid $id,
        string $name,
        ProviderType $type,
        string $url,
        ?string $apiToken,
        ?string $username = null,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
        $this->url = $url;
        $this->apiToken = $apiToken;
        $this->username = $username;
        $this->status = ProviderStatus::Pending;
        $this->lastSyncAt = null;
        $this->projects = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public static function create(
        string $name,
        ProviderType $type,
        string $url,
        ?string $apiToken = null,
        ?string $username = null,
    ): self {
        return new self(
            id: Uuid::v7(),
            name: $name,
            type: $type,
            url: \rtrim($url, '/'),
            apiToken: $apiToken,
            username: $username,
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

    public function getType(): ProviderType
    {
        return $this->type;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getStatus(): ProviderStatus
    {
        return $this->status;
    }

    public function getLastSyncAt(): ?DateTimeImmutable
    {
        return $this->lastSyncAt;
    }

    /** @return Collection<int, Project> */
    public function getProjects(): Collection
    {
        return $this->projects;
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
        ?string $url = null,
        ?string $apiToken = null,
        ?string $username = null,
    ): void {
        if ($name !== null) {
            $this->name = $name;
        }
        if ($url !== null) {
            $this->url = \rtrim($url, '/');
        }
        if ($apiToken !== null) {
            $this->apiToken = $apiToken;
        }
        if ($username !== null) {
            $this->username = $username;
        }
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markConnected(): void
    {
        $this->status = ProviderStatus::Connected;
        $this->lastSyncAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markError(): void
    {
        $this->status = ProviderStatus::Error;
        $this->updatedAt = new DateTimeImmutable();
    }
}
