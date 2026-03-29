<?php

declare(strict_types=1);

namespace App\Activity\Application\CommandHandler;

use App\Activity\Application\Command\CreateActivityEventCommand;
use App\Activity\Application\DTO\ActivityEventOutput;
use App\Activity\Application\Mapper\ActivityEventMapper;
use App\Activity\Domain\Model\ActivityEvent;
use App\Activity\Domain\Repository\ActivityEventRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateActivityEventHandler
{
    public function __construct(
        private ActivityEventRepositoryInterface $activityEventRepository,
    ) {
    }

    public function __invoke(CreateActivityEventCommand $command): ActivityEventOutput
    {
        $input = $command->input;

        $event = ActivityEvent::create(
            type: $input->type,
            entityType: $input->entityType,
            entityId: $input->entityId,
            payload: $input->payload,
            userId: $input->userId,
        );

        $this->activityEventRepository->save($event);

        return ActivityEventMapper::toOutput($event);
    }
}
