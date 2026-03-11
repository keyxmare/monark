<?php

declare(strict_types=1);

namespace App\Assessment\Domain\Event;

final readonly class QuizDeleted
{
    public function __construct(
        public string $quizId,
    ) {
    }
}
