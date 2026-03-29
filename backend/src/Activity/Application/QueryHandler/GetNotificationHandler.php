<?php

declare(strict_types=1);

namespace App\Activity\Application\QueryHandler;

use App\Activity\Application\DTO\NotificationOutput;
use App\Activity\Application\Mapper\NotificationMapper;
use App\Activity\Application\Query\GetNotificationQuery;
use App\Activity\Domain\Repository\NotificationRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetNotificationHandler
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
    ) {
    }

    public function __invoke(GetNotificationQuery $query): NotificationOutput
    {
        $notification = $this->notificationRepository->findById(Uuid::fromString($query->notificationId));
        if ($notification === null) {
            throw NotFoundException::forEntity('Notification', $query->notificationId);
        }

        return NotificationMapper::toOutput($notification);
    }
}
