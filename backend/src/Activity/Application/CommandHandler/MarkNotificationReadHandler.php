<?php

declare(strict_types=1);

namespace App\Activity\Application\CommandHandler;

use App\Activity\Application\Command\MarkNotificationReadCommand;
use App\Activity\Application\DTO\NotificationOutput;
use App\Activity\Application\Mapper\NotificationMapper;
use App\Activity\Domain\Repository\NotificationRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class MarkNotificationReadHandler
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
    ) {
    }

    public function __invoke(MarkNotificationReadCommand $command): NotificationOutput
    {
        $notification = $this->notificationRepository->findById(Uuid::fromString($command->notificationId));
        if ($notification === null) {
            throw NotFoundException::forEntity('Notification', $command->notificationId);
        }

        $notification->markAsRead();
        $this->notificationRepository->save($notification);

        return NotificationMapper::toOutput($notification);
    }
}
