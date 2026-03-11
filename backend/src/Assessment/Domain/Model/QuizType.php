<?php

declare(strict_types=1);

namespace App\Assessment\Domain\Model;

enum QuizType: string
{
    case Quiz = 'quiz';
    case Survey = 'survey';
}
