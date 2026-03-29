<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Model;

use App\Shared\Domain\ValueObject\RepositoryUrl;
use App\Shared\Domain\ValueObject\Slug;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'catalog_projects')]
#[ORM\UniqueConstraint(name: 'uniq_project_slug', columns: ['slug'])]
final class Project
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'app_slug', length: 255, unique: true)]
    private Slug $slug;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\Column(type: 'app_repository_url', length: 500)]
    private RepositoryUrl $repositoryUrl;

    #[ORM\Column(length: 100)]
    private string $defaultBranch;

    #[ORM\Column(type: 'string', enumType: ProjectVisibility::class)]
    private ProjectVisibility $visibility;

    #[ORM\Column(type: 'uuid')]
    private Uuid $ownerId;

    #[ORM\ManyToOne(targetEntity: Provider::class, inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Provider $provider;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $externalId;

    /** @var Collection<int, TechStack> */
    #[ORM\OneToMany(targetEntity: TechStack::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $techStacks;

    /** @var Collection<int, MergeRequest> */
    #[ORM\OneToMany(targetEntity: MergeRequest::class, mappedBy: 'project', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $mergeRequests;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $lastSyncedAt;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    private function __construct(
        Uuid $id,
        string $name,
        Slug $slug,
        ?string $description,
        RepositoryUrl $repositoryUrl,
        string $defaultBranch,
        ProjectVisibility $visibility,
        Uuid $ownerId,
        ?Provider $provider = null,
        ?string $externalId = null,
    ) {
        if (trim($name) === '') {
            throw new \InvalidArgumentException('Project name must not be blank.');
        }
        if (trim($defaultBranch) === '') {
            throw new \InvalidArgumentException('Project default branch must not be blank.');
        }

        $this->id = $id;
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->repositoryUrl = $repositoryUrl;
        $this->defaultBranch = $defaultBranch;
        $this->visibility = $visibility;
        $this->ownerId = $ownerId;
        $this->provider = $provider;
        $this->externalId = $externalId;
        $this->techStacks = new ArrayCollection();
        $this->mergeRequests = new ArrayCollection();
        $this->lastSyncedAt = null;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public static function create(
        string $name,
        string $slug,
        ?string $description,
        string $repositoryUrl,
        string $defaultBranch,
        ProjectVisibility $visibility,
        Uuid $ownerId,
        ?Provider $provider = null,
        ?string $externalId = null,
    ): self {
        return new self(
            id: Uuid::v7(),
            name: $name,
            slug: new Slug($slug),
            description: $description,
            repositoryUrl: new RepositoryUrl($repositoryUrl),
            defaultBranch: $defaultBranch,
            visibility: $visibility,
            ownerId: $ownerId,
            provider: $provider,
            externalId: $externalId,
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

    public function getSlug(): string
    {
        return $this->slug->value();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getRepositoryUrl(): string
    {
        return $this->repositoryUrl->value();
    }

    public function getDefaultBranch(): string
    {
        return $this->defaultBranch;
    }

    public function getVisibility(): ProjectVisibility
    {
        return $this->visibility;
    }

    public function getOwnerId(): Uuid
    {
        return $this->ownerId;
    }

    public function getProvider(): ?Provider
    {
        return $this->provider;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    /** @return Collection<int, TechStack> */
    public function getTechStacks(): Collection
    {
        return $this->techStacks;
    }

    /** @return Collection<int, MergeRequest> */
    public function getMergeRequests(): Collection
    {
        return $this->mergeRequests;
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
        ?string $slug = null,
        ?string $description = null,
        ?string $repositoryUrl = null,
        ?string $defaultBranch = null,
        ?ProjectVisibility $visibility = null,
    ): void {
        if ($name !== null) {
            $this->name = $name;
        }
        if ($slug !== null) {
            $this->slug = new Slug($slug);
        }
        if ($description !== null) {
            $this->description = $description;
        }
        if ($repositoryUrl !== null) {
            $this->repositoryUrl = new RepositoryUrl($repositoryUrl);
        }
        if ($defaultBranch !== null) {
            $this->defaultBranch = $defaultBranch;
        }
        if ($visibility !== null) {
            $this->visibility = $visibility;
        }
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getLastSyncedAt(): ?DateTimeImmutable
    {
        return $this->lastSyncedAt;
    }

    public function markSynced(): void
    {
        $this->lastSyncedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }
}
