<?php

declare(strict_types=1);

namespace App\Assessment\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'assessment_attempts')]
final class Attempt
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'integer')]
    private int $score;

    #[ORM\Column(type: 'string', enumType: AttemptStatus::class)]
    private AttemptStatus $status;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $startedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $finishedAt;

    #[ORM\Column(length: 36)]
    private string $userId;

    #[ORM\Column(length: 36)]
    private string $quizId;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    private function __construct(
        Uuid $id,
        int $score,
        AttemptStatus $status,
        DateTimeImmutable $startedAt,
        ?DateTimeImmutable $finishedAt,
        string $userId,
        string $quizId,
    ) {
        $this->id = $id;
        $this->score = $score;
        $this->status = $status;
        $this->startedAt = $startedAt;
        $this->finishedAt = $finishedAt;
        $this->userId = $userId;
        $this->quizId = $quizId;
        $this->createdAt = new DateTimeImmutable();
    }

    public static function create(
        string $userId,
        string $quizId,
        int $score = 0,
        AttemptStatus $status = AttemptStatus::Started,
        ?DateTimeImmutable $startedAt = null,
        ?DateTimeImmutable $finishedAt = null,
    ): self {
        return new self(
            id: Uuid::v7(),
            score: $score,
            status: $status,
            startedAt: $startedAt ?? new DateTimeImmutable(),
            finishedAt: $finishedAt,
            userId: $userId,
            quizId: $quizId,
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function getStatus(): AttemptStatus
    {
        return $this->status;
    }

    public function getStartedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getFinishedAt(): ?DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getQuizId(): string
    {
        return $this->quizId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
