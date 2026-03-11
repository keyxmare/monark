<?php

declare(strict_types=1);

namespace App\Activity\Application\QueryHandler;

use App\Activity\Application\DTO\DashboardMetric;
use App\Activity\Application\DTO\DashboardOutput;
use App\Activity\Application\Query\GetDashboardQuery;
use App\Activity\Domain\Repository\ActivityEventRepositoryInterface;
use App\Activity\Domain\Repository\NotificationRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetDashboardHandler
{
    public function __construct(
        private ActivityEventRepositoryInterface $activityEventRepository,
        private NotificationRepositoryInterface $notificationRepository,
    ) {
    }

    public function __invoke(GetDashboardQuery $query): DashboardOutput
    {
        $totalEvents = $this->activityEventRepository->count();
        $totalNotifications = $this->notificationRepository->countByUser($query->userId);
        $unreadNotifications = $this->notificationRepository->countUnreadByUser($query->userId);

        return new DashboardOutput(
            metrics: [
                new DashboardMetric(label: 'Total Events', value: $totalEvents),
                new DashboardMetric(label: 'Notifications', value: $totalNotifications),
                new DashboardMetric(label: 'Unread', value: $unreadNotifications),
            ],
        );
    }
}
