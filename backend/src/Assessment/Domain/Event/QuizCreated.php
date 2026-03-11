<?php

declare(strict_types=1);

namespace App\Assessment\Domain\Event;

final readonly class QuizCreated
{
    public function __construct(
        public string $quizId,
        public string $title,
    ) {
    }
}
