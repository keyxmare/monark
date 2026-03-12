<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

use App\Catalog\Domain\Model\Provider;

final readonly class ProviderOutput
{
    public function __construct(
        public string $id,
        public string $name,
        public string $type,
        public string $url,
        public ?string $username,
        public string $status,
        public int $projectsCount,
        public ?string $lastSyncAt,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(Provider $provider): self
    {
        return new self(
            id: $provider->getId()->toRfc4122(),
            name: $provider->getName(),
            type: $provider->getType()->value,
            url: $provider->getUrl(),
            username: $provider->getUsername(),
            status: $provider->getStatus()->value,
            projectsCount: $provider->getProjects()->count(),
            lastSyncAt: $provider->getLastSyncAt()?->format(\DateTimeInterface::ATOM),
            createdAt: $provider->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $provider->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
