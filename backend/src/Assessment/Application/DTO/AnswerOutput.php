<?php

declare(strict_types=1);

namespace App\Assessment\Application\DTO;

use App\Assessment\Domain\Model\Answer;

final readonly class AnswerOutput
{
    public function __construct(
        public string $id,
        public string $content,
        public bool $isCorrect,
        public int $position,
        public string $questionId,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(Answer $answer): self
    {
        return new self(
            id: $answer->getId()->toRfc4122(),
            content: $answer->getContent(),
            isCorrect: $answer->isCorrect(),
            position: $answer->getPosition(),
            questionId: $answer->getQuestion()->getId()->toRfc4122(),
            createdAt: $answer->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $answer->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
