<?php

declare(strict_types=1);

namespace App\Activity\Application\QueryHandler;

use App\Activity\Application\DTO\NotificationListOutput;
use App\Activity\Application\DTO\NotificationOutput;
use App\Activity\Application\Query\ListNotificationsQuery;
use App\Activity\Domain\Repository\NotificationRepositoryInterface;
use App\Shared\Application\DTO\PaginatedOutput;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListNotificationsHandler
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
    ) {
    }

    public function __invoke(ListNotificationsQuery $query): NotificationListOutput
    {
        $notifications = $this->notificationRepository->findByUser($query->userId, $query->page, $query->perPage);
        $total = $this->notificationRepository->countByUser($query->userId);

        $items = \array_map(
            static fn (mixed $notification) => NotificationOutput::fromEntity($notification),
            $notifications,
        );

        return new NotificationListOutput(
            pagination: new PaginatedOutput(
                items: $items,
                total: $total,
                page: $query->page,
                perPage: $query->perPage,
            ),
        );
    }
}
