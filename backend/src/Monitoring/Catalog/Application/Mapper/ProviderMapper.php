<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\Mapper;

use App\Monitoring\Catalog\Application\DTO\ProviderOutput;
use App\Monitoring\Catalog\Domain\Model\Provider;
use DateTimeInterface;

final class ProviderMapper
{
    public static function toOutput(Provider $provider): ProviderOutput
    {
        return new ProviderOutput(
            id: $provider->getId()->toRfc4122(),
            name: $provider->getName(),
            type: $provider->getType()->value,
            url: $provider->getUrl(),
            username: $provider->getUsername(),
            status: $provider->getStatus()->value,
            projectsCount: $provider->getProjects()->count(),
            lastSyncAt: $provider->getLastSyncAt()?->format(DateTimeInterface::ATOM),
            createdAt: $provider->getCreatedAt()->format(DateTimeInterface::ATOM),
            updatedAt: $provider->getUpdatedAt()->format(DateTimeInterface::ATOM),
        );
    }
}
