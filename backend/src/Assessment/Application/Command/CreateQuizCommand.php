<?php

declare(strict_types=1);

namespace App\Assessment\Application\Command;

use App\Assessment\Application\DTO\CreateQuizInput;

final readonly class CreateQuizCommand
{
    public function __construct(
        public CreateQuizInput $input,
    ) {
    }
}
