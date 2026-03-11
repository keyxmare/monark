<?php

declare(strict_types=1);

namespace App\Activity\Application\Command;

use App\Activity\Application\DTO\CreateActivityEventInput;

final readonly class CreateActivityEventCommand
{
    public function __construct(
        public CreateActivityEventInput $input,
    ) {
    }
}
