<?php

declare(strict_types=1);

use App\Activity\Application\DTO\DashboardOutput;
use App\Activity\Application\Query\GetDashboardQuery;
use App\Activity\Application\QueryHandler\GetDashboardHandler;
use App\Activity\Domain\Model\ActivityEvent;
use App\Activity\Domain\Model\Notification;
use App\Activity\Domain\Repository\ActivityEventRepositoryInterface;
use App\Activity\Domain\Repository\NotificationRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubDashboardEventRepo(int $count = 0): ActivityEventRepositoryInterface
{
    return new class ($count) implements ActivityEventRepositoryInterface {
        public function __construct(private readonly int $total) {}
        public function findById(Uuid $id): ?ActivityEvent { return null; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function count(): int { return $this->total; }
        public function save(ActivityEvent $event): void {}
    };
}

function stubDashboardNotificationRepo(int $total = 0, int $unread = 0): NotificationRepositoryInterface
{
    return new class ($total, $unread) implements NotificationRepositoryInterface {
        public function __construct(private readonly int $total, private readonly int $unread) {}
        public function findById(Uuid $id): ?Notification { return null; }
        public function findByUser(string $userId, int $page = 1, int $perPage = 20): array { return []; }
        public function countByUser(string $userId): int { return $this->total; }
        public function countUnreadByUser(string $userId): int { return $this->unread; }
        public function save(Notification $notification): void {}
    };
}

describe('GetDashboardHandler', function () {
    it('returns dashboard metrics', function () {
        $handler = new GetDashboardHandler(
            stubDashboardEventRepo(5),
            stubDashboardNotificationRepo(10, 3),
        );

        $result = $handler(new GetDashboardQuery('00000000-0000-0000-0000-000000000001'));

        expect($result)->toBeInstanceOf(DashboardOutput::class);
        expect($result->metrics)->toHaveCount(3);
        expect($result->metrics[0]->label)->toBe('Total Events');
        expect($result->metrics[0]->value)->toBe(5);
        expect($result->metrics[1]->label)->toBe('Notifications');
        expect($result->metrics[1]->value)->toBe(10);
        expect($result->metrics[2]->label)->toBe('Unread');
        expect($result->metrics[2]->value)->toBe(3);
    });
});
