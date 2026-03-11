<?php

declare(strict_types=1);

namespace App\Assessment\Application\Command;

use App\Assessment\Application\DTO\UpdateQuestionInput;

final readonly class UpdateQuestionCommand
{
    public function __construct(
        public string $questionId,
        public UpdateQuestionInput $input,
    ) {
    }
}
