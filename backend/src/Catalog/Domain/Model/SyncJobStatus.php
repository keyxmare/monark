<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Model;

enum SyncJobStatus: string
{
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
}
