<?php

declare(strict_types=1);

namespace App\Activity\Application\CommandHandler;

use App\Activity\Application\Command\CreateNotificationCommand;
use App\Activity\Application\DTO\NotificationOutput;
use App\Activity\Application\Mapper\NotificationMapper;
use App\Activity\Domain\Model\Notification;
use App\Activity\Domain\Model\NotificationChannel;
use App\Activity\Domain\Repository\NotificationRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateNotificationHandler
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
    ) {
    }

    public function __invoke(CreateNotificationCommand $command): NotificationOutput
    {
        $input = $command->input;

        $notification = Notification::create(
            title: $input->title,
            message: $input->message,
            channel: NotificationChannel::from($input->channel),
            userId: $input->userId,
        );

        $this->notificationRepository->save($notification);

        return NotificationMapper::toOutput($notification);
    }
}
