<?php

declare(strict_types=1);

namespace App\Assessment\Application\DTO;

use App\Assessment\Domain\Model\Quiz;

final readonly class QuizOutput
{
    public function __construct(
        public string $id,
        public string $title,
        public string $slug,
        public string $description,
        public string $type,
        public string $status,
        public ?string $startsAt,
        public ?string $endsAt,
        public ?int $timeLimit,
        public string $authorId,
        public int $questionCount,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(Quiz $quiz): self
    {
        return new self(
            id: $quiz->getId()->toRfc4122(),
            title: $quiz->getTitle(),
            slug: $quiz->getSlug(),
            description: $quiz->getDescription(),
            type: $quiz->getType()->value,
            status: $quiz->getStatus()->value,
            startsAt: $quiz->getStartsAt()?->format(\DateTimeInterface::ATOM),
            endsAt: $quiz->getEndsAt()?->format(\DateTimeInterface::ATOM),
            timeLimit: $quiz->getTimeLimit(),
            authorId: $quiz->getAuthorId(),
            questionCount: $quiz->getQuestionCount(),
            createdAt: $quiz->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $quiz->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
