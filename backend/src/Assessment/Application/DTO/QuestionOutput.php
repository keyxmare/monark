<?php

declare(strict_types=1);

namespace App\Assessment\Application\DTO;

use App\Assessment\Domain\Model\Question;

final readonly class QuestionOutput
{
    public function __construct(
        public string $id,
        public string $type,
        public string $content,
        public string $level,
        public int $score,
        public int $position,
        public string $quizId,
        public int $answerCount,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(Question $question): self
    {
        return new self(
            id: $question->getId()->toRfc4122(),
            type: $question->getType()->value,
            content: $question->getContent(),
            level: $question->getLevel()->value,
            score: $question->getScore(),
            position: $question->getPosition(),
            quizId: $question->getQuiz()->getId()->toRfc4122(),
            answerCount: $question->getAnswerCount(),
            createdAt: $question->getCreatedAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $question->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
