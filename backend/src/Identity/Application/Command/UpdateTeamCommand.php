<?php

declare(strict_types=1);

namespace App\Identity\Application\Command;

use App\Identity\Application\DTO\UpdateTeamInput;

final readonly class UpdateTeamCommand
{
    public function __construct(
        public string $teamId,
        public UpdateTeamInput $input,
    ) {
    }
}
