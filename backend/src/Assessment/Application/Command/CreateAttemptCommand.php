<?php

declare(strict_types=1);

namespace App\Assessment\Application\Command;

use App\Assessment\Application\DTO\CreateAttemptInput;

final readonly class CreateAttemptCommand
{
    public function __construct(
        public CreateAttemptInput $input,
    ) {
    }
}
