<?php

declare(strict_types=1);

namespace App\Tests\Factory\Assessment;

use App\Assessment\Domain\Model\Quiz;
use App\Assessment\Domain\Model\QuizStatus;
use App\Assessment\Domain\Model\QuizType;

final class QuizFactory
{
    public static function create(array $overrides = []): Quiz
    {
        return Quiz::create(
            title: $overrides['title'] ?? 'PHP Fundamentals',
            slug: $overrides['slug'] ?? 'php-fundamentals',
            description: $overrides['description'] ?? 'A quiz about PHP basics',
            type: $overrides['type'] ?? QuizType::Quiz,
            status: $overrides['status'] ?? QuizStatus::Draft,
            startsAt: $overrides['startsAt'] ?? null,
            endsAt: $overrides['endsAt'] ?? null,
            timeLimit: $overrides['timeLimit'] ?? null,
            authorId: $overrides['authorId'] ?? '00000000-0000-0000-0000-000000000001',
        );
    }
}
