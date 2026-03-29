<?php

declare(strict_types=1);

namespace App\Activity\Application\Mapper;

use App\Activity\Application\DTO\SyncTaskOutput;
use App\Activity\Domain\Model\SyncTask;
use DateTimeInterface;

final class SyncTaskMapper
{
    public static function toOutput(SyncTask $syncTask): SyncTaskOutput
    {
        return new SyncTaskOutput(
            id: $syncTask->getId()->toRfc4122(),
            type: $syncTask->getType()->value,
            severity: $syncTask->getSeverity()->value,
            title: $syncTask->getTitle(),
            description: $syncTask->getDescription(),
            status: $syncTask->getStatus()->value,
            metadata: $syncTask->getMetadata(),
            projectId: $syncTask->getProjectId()->toRfc4122(),
            resolvedAt: $syncTask->getResolvedAt()?->format(DateTimeInterface::ATOM),
            createdAt: $syncTask->getCreatedAt()->format(DateTimeInterface::ATOM),
            updatedAt: $syncTask->getUpdatedAt()->format(DateTimeInterface::ATOM),
        );
    }
}
