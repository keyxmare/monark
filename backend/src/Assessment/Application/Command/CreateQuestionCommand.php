<?php

declare(strict_types=1);

namespace App\Assessment\Application\Command;

use App\Assessment\Application\DTO\CreateQuestionInput;

final readonly class CreateQuestionCommand
{
    public function __construct(
        public CreateQuestionInput $input,
    ) {
    }
}
