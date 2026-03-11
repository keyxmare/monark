<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Model;

enum Severity: string
{
    case Critical = 'critical';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';
}
