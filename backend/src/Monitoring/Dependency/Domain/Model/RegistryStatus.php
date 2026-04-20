<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Domain\Model;

enum RegistryStatus: string
{
    case Pending = 'pending';
    case Synced = 'synced';
    case NotFound = 'not_found';
    case Deprecated = 'deprecated';
}
