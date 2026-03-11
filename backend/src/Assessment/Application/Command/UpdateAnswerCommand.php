<?php

declare(strict_types=1);

namespace App\Assessment\Application\Command;

use App\Assessment\Application\DTO\UpdateAnswerInput;

final readonly class UpdateAnswerCommand
{
    public function __construct(
        public string $answerId,
        public UpdateAnswerInput $input,
    ) {
    }
}
