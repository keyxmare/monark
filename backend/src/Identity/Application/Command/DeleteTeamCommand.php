<?php

declare(strict_types=1);

namespace App\Identity\Application\Command;

final readonly class DeleteTeamCommand
{
    public function __construct(
        public string $teamId,
    ) {
    }
}
