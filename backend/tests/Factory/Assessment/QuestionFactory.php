<?php

declare(strict_types=1);

namespace App\Tests\Factory\Assessment;

use App\Assessment\Domain\Model\Question;
use App\Assessment\Domain\Model\QuestionLevel;
use App\Assessment\Domain\Model\QuestionType;
use App\Assessment\Domain\Model\Quiz;

final class QuestionFactory
{
    public static function create(Quiz $quiz, array $overrides = []): Question
    {
        return Question::create(
            type: $overrides['type'] ?? QuestionType::SingleChoice,
            content: $overrides['content'] ?? 'What is PHP?',
            level: $overrides['level'] ?? QuestionLevel::Easy,
            score: $overrides['score'] ?? 1,
            position: $overrides['position'] ?? 1,
            quiz: $quiz,
        );
    }
}
