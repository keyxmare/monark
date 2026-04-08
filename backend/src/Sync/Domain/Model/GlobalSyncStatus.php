<?php

declare(strict_types=1);

namespace App\Sync\Domain\Model;

enum GlobalSyncStatus: string
{
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
}
