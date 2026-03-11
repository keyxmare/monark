<?php

declare(strict_types=1);

namespace App\Identity\Application\Command;

use App\Identity\Application\DTO\CreateTeamInput;

final readonly class CreateTeamCommand
{
    public function __construct(
        public CreateTeamInput $input,
    ) {
    }
}
