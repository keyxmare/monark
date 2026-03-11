<?php

declare(strict_types=1);

namespace App\Tests\Factory\Assessment;

use App\Assessment\Domain\Model\Answer;
use App\Assessment\Domain\Model\Question;

final class AnswerFactory
{
    public static function create(Question $question, array $overrides = []): Answer
    {
        return Answer::create(
            content: $overrides['content'] ?? 'A programming language',
            isCorrect: $overrides['isCorrect'] ?? true,
            position: $overrides['position'] ?? 1,
            question: $question,
        );
    }
}
