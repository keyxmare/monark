<?php

declare(strict_types=1);

namespace App\Activity\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'activity_events')]
final class ActivityEvent
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $type;

    #[ORM\Column(type: 'string', length: 255)]
    private string $entityType;

    #[ORM\Column(type: 'string', length: 255)]
    private string $entityId;

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    private array $payload;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $occurredAt;

    #[ORM\Column(type: 'string', length: 255)]
    private string $userId;

    /** @param array<string, mixed> $payload */
    private function __construct(
        Uuid $id,
        string $type,
        string $entityType,
        string $entityId,
        array $payload,
        DateTimeImmutable $occurredAt,
        string $userId,
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->payload = $payload;
        $this->occurredAt = $occurredAt;
        $this->userId = $userId;
    }

    /** @param array<string, mixed> $payload */
    public static function create(
        string $type,
        string $entityType,
        string $entityId,
        array $payload,
        string $userId,
    ): self {
        return new self(
            id: Uuid::v7(),
            type: $type,
            entityType: $entityType,
            entityId: $entityId,
            payload: $payload,
            occurredAt: new DateTimeImmutable(),
            userId: $userId,
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    /** @return array<string, mixed> */
    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }
}
