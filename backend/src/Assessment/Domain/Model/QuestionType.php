<?php

declare(strict_types=1);

namespace App\Assessment\Domain\Model;

enum QuestionType: string
{
    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case Text = 'text';
    case Code = 'code';
}
