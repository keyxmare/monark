<?php

declare(strict_types=1);

namespace App\Activity\Domain\Model;

enum SyncTaskSeverity: string
{
    case Critical = 'critical';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';
    case Info = 'info';
}
