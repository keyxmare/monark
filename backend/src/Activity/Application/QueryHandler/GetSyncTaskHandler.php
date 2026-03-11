<?php

declare(strict_types=1);

namespace App\Activity\Application\QueryHandler;

use App\Activity\Application\DTO\SyncTaskOutput;
use App\Activity\Application\Query\GetSyncTaskQuery;
use App\Activity\Domain\Repository\SyncTaskRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetSyncTaskHandler
{
    public function __construct(
        private SyncTaskRepositoryInterface $syncTaskRepository,
    ) {
    }

    public function __invoke(GetSyncTaskQuery $query): SyncTaskOutput
    {
        $syncTask = $this->syncTaskRepository->findById(Uuid::fromString($query->id));
        if ($syncTask === null) {
            throw NotFoundException::forEntity('SyncTask', $query->id);
        }

        return SyncTaskOutput::fromEntity($syncTask);
    }
}
