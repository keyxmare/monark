<?php

declare(strict_types=1);

namespace App\Assessment\Application\Command;

use App\Assessment\Application\DTO\CreateAnswerInput;

final readonly class CreateAnswerCommand
{
    public function __construct(
        public CreateAnswerInput $input,
    ) {
    }
}
