<?php

declare(strict_types=1);

namespace App\Activity\Application\CommandHandler;

use App\Activity\Application\Command\UpdateSyncTaskStatusCommand;
use App\Activity\Application\DTO\SyncTaskOutput;
use App\Activity\Domain\Model\SyncTaskStatus;
use App\Activity\Domain\Repository\SyncTaskRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UpdateSyncTaskStatusHandler
{
    public function __construct(
        private SyncTaskRepositoryInterface $syncTaskRepository,
    ) {
    }

    public function __invoke(UpdateSyncTaskStatusCommand $command): SyncTaskOutput
    {
        $syncTask = $this->syncTaskRepository->findById(Uuid::fromString($command->id));
        if ($syncTask === null) {
            throw NotFoundException::forEntity('SyncTask', $command->id);
        }

        $syncTask->changeStatus(SyncTaskStatus::from($command->status));
        $this->syncTaskRepository->save($syncTask);

        return SyncTaskOutput::fromEntity($syncTask);
    }
}
