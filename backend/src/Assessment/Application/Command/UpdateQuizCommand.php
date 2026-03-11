<?php

declare(strict_types=1);

namespace App\Assessment\Application\Command;

use App\Assessment\Application\DTO\UpdateQuizInput;

final readonly class UpdateQuizCommand
{
    public function __construct(
        public string $quizId,
        public UpdateQuizInput $input,
    ) {
    }
}
