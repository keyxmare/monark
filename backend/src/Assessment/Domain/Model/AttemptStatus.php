<?php

declare(strict_types=1);

namespace App\Assessment\Domain\Model;

enum AttemptStatus: string
{
    case Started = 'started';
    case Submitted = 'submitted';
    case Graded = 'graded';
}
