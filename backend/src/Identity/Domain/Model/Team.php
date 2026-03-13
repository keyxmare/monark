<?php

declare(strict_types=1);

namespace App\Identity\Domain\Model;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'identity_teams')]
#[ORM\UniqueConstraint(name: 'uniq_team_slug', columns: ['slug'])]
final class Team
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 150)]
    private string $name;

    #[ORM\Column(length: 150, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    /** @var Collection<int, User> */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'teams')]
    #[ORM\JoinTable(name: 'identity_team_members')]
    private Collection $members;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    private function __construct(
        Uuid $id,
        string $name,
        string $slug,
        ?string $description,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->members = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public static function create(
        string $name,
        string $slug,
        ?string $description = null,
    ): self {
        return new self(
            id: Uuid::v7(),
            name: $name,
            slug: $slug,
            description: $description,
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
        return $this->slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /** @return Collection<int, User> */
    public function getMembers(): Collection
    {
        return $this->members;
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
    ): void {
        if ($name !== null) {
            $this->name = $name;
        }
        if ($slug !== null) {
            $this->slug = $slug;
        }
        if ($description !== null) {
            $this->description = $description;
        }
        $this->updatedAt = new DateTimeImmutable();
    }

    public function addMember(User $user): void
    {
        if (!$this->members->contains($user)) {
            $this->members->add($user);
        }
    }

    public function removeMember(User $user): void
    {
        $this->members->removeElement($user);
    }

    public function getMemberCount(): int
    {
        return $this->members->count();
    }
}
