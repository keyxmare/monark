<?php

declare(strict_types=1);

namespace App\Activity\Domain\Model;

enum SyncTaskStatus: string
{
    case Open = 'open';
    case Acknowledged = 'acknowledged';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';
}
