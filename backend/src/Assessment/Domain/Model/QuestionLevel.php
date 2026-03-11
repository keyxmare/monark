<?php

declare(strict_types=1);

namespace App\Assessment\Domain\Model;

enum QuestionLevel: string
{
    case Easy = 'easy';
    case Medium = 'medium';
    case Hard = 'hard';
}
