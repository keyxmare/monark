<?php

declare(strict_types=1);

namespace App\Activity\Application\QueryHandler;

use App\Activity\Application\DTO\SyncTaskListOutput;
use App\Activity\Application\Mapper\SyncTaskMapper;
use App\Activity\Application\Query\ListSyncTasksQuery;
use App\Activity\Domain\Model\SyncTaskSeverity;
use App\Activity\Domain\Model\SyncTaskStatus;
use App\Activity\Domain\Model\SyncTaskType;
use App\Activity\Domain\Repository\SyncTaskRepositoryInterface;
use App\Shared\Application\DTO\PaginatedOutput;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListSyncTasksHandler
{
    public function __construct(
        private SyncTaskRepositoryInterface $syncTaskRepository,
    ) {
    }

    public function __invoke(ListSyncTasksQuery $query): SyncTaskListOutput
    {
        $status = $query->status !== null ? SyncTaskStatus::from($query->status) : null;
        $type = $query->type !== null ? SyncTaskType::from($query->type) : null;
        $severity = $query->severity !== null ? SyncTaskSeverity::from($query->severity) : null;
        $projectId = $query->projectId !== null ? Uuid::fromString($query->projectId) : null;

        $tasks = $this->syncTaskRepository->findFiltered(
            status: $status,
            type: $type,
            severity: $severity,
            projectId: $projectId,
            page: $query->page,
            perPage: $query->perPage,
        );

        $total = $this->syncTaskRepository->countFiltered(
            status: $status,
            type: $type,
            severity: $severity,
            projectId: $projectId,
        );

        $items = \array_map(
            static fn (mixed $task) => SyncTaskMapper::toOutput($task),
            $tasks,
        );

        return new SyncTaskListOutput(
            pagination: new PaginatedOutput(
                items: $items,
                total: $total,
                page: $query->page,
                perPage: $query->perPage,
            ),
        );
    }
}
