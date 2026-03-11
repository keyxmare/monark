<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Model;

enum PipelineStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Success = 'success';
    case Failed = 'failed';
}
