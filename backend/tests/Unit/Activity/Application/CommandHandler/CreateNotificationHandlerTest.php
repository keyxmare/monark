<?php

declare(strict_types=1);

use App\Activity\Application\Command\CreateNotificationCommand;
use App\Activity\Application\CommandHandler\CreateNotificationHandler;
use App\Activity\Application\DTO\CreateNotificationInput;
use App\Activity\Application\DTO\NotificationOutput;
use App\Activity\Domain\Model\Notification;
use App\Activity\Domain\Repository\NotificationRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubNotificationRepoForCreate(): NotificationRepositoryInterface
{
    return new class implements NotificationRepositoryInterface {
        public ?Notification $saved = null;
        public function findById(Uuid $id): ?Notification { return null; }
        public function findByUser(string $userId, int $page = 1, int $perPage = 20): array { return []; }
        public function countByUser(string $userId): int { return 0; }
        public function countUnreadByUser(string $userId): int { return 0; }
        public function save(Notification $notification): void { $this->saved = $notification; }
    };
}

describe('CreateNotificationHandler', function () {
    it('creates a notification successfully', function () {
        $repo = stubNotificationRepoForCreate();
        $handler = new CreateNotificationHandler($repo);

        $input = new CreateNotificationInput(
            title: 'New Deploy',
            message: 'Your project has been deployed.',
            channel: 'in_app',
            userId: '00000000-0000-0000-0000-000000000001',
        );

        $result = $handler(new CreateNotificationCommand($input));

        expect($result)->toBeInstanceOf(NotificationOutput::class);
        expect($result->title)->toBe('New Deploy');
        expect($result->message)->toBe('Your project has been deployed.');
        expect($result->channel)->toBe('in_app');
        expect($result->readAt)->toBeNull();
        expect($repo->saved)->not->toBeNull();
    });
});
