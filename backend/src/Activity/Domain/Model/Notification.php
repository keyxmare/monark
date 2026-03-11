<?php

declare(strict_types=1);

namespace App\Activity\Domain\Model;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'notifications')]
final class Notification
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(type: 'string', enumType: NotificationChannel::class)]
    private NotificationChannel $channel;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $readAt;

    #[ORM\Column(type: 'string', length: 255)]
    private string $userId;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    private function __construct(
        Uuid $id,
        string $title,
        string $message,
        NotificationChannel $channel,
        string $userId,
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->message = $message;
        $this->channel = $channel;
        $this->readAt = null;
        $this->userId = $userId;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function create(
        string $title,
        string $message,
        NotificationChannel $channel,
        string $userId,
    ): self {
        return new self(
            id: Uuid::v7(),
            title: $title,
            message: $message,
            channel: $channel,
            userId: $userId,
        );
    }

    public function markAsRead(): void
    {
        $this->readAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getChannel(): NotificationChannel
    {
        return $this->channel;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
