<?php

declare(strict_types=1);

namespace App\Identity\Application\DTO;

use App\Identity\Domain\Model\Team;

final readonly class TeamOutput
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public int $memberCount,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(Team $team): self
    {
        return new self(
            id: $team->getId()->toRfc4122(),
            name: $team->getName(),
            slug: $team->getSlug(),
            description: $team->getDescription(),
            memberCount: $team->getMemberCount(),
            createdAt: $team->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $team->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
