<?php

declare(strict_types=1);

namespace App\Identity\Domain\Model;

enum TokenProvider: string
{
    case Gitlab = 'gitlab';
    case Github = 'github';
}
