<?php

declare(strict_types=1);

namespace App\Activity\Application\QueryHandler;

use App\Activity\Application\DTO\ActivityEventOutput;
use App\Activity\Application\Mapper\ActivityEventMapper;
use App\Activity\Application\Query\GetActivityEventQuery;
use App\Activity\Domain\Repository\ActivityEventRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetActivityEventHandler
{
    public function __construct(
        private ActivityEventRepositoryInterface $activityEventRepository,
    ) {
    }

    public function __invoke(GetActivityEventQuery $query): ActivityEventOutput
    {
        $event = $this->activityEventRepository->findById(Uuid::fromString($query->eventId));
        if ($event === null) {
            throw NotFoundException::forEntity('ActivityEvent', $query->eventId);
        }

        return ActivityEventMapper::toOutput($event);
    }
}
