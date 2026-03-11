<?php

declare(strict_types=1);

namespace App\Tests\Factory\Assessment;

use App\Assessment\Domain\Model\Attempt;
use App\Assessment\Domain\Model\AttemptStatus;

final class AttemptFactory
{
    public static function create(array $overrides = []): Attempt
    {
        return Attempt::create(
            userId: $overrides['userId'] ?? '00000000-0000-0000-0000-000000000001',
            quizId: $overrides['quizId'] ?? '00000000-0000-0000-0000-000000000002',
            score: $overrides['score'] ?? 0,
            status: $overrides['status'] ?? AttemptStatus::Started,
        );
    }
}
