<?php

declare(strict_types=1);

namespace App\Assessment\Application\Command;

final readonly class DeleteQuestionCommand
{
    public function __construct(
        public string $questionId,
    ) {
    }
}
