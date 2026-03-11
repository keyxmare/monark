<?php

declare(strict_types=1);

namespace App\Tests\Factory\Identity;

use App\Identity\Domain\Model\Team;

final class TeamFactory
{
    public static function create(array $overrides = []): Team
    {
        return Team::create(
            name: $overrides['name'] ?? 'Engineering',
            slug: $overrides['slug'] ?? 'engineering',
            description: $overrides['description'] ?? 'The engineering team',
        );
    }
}
