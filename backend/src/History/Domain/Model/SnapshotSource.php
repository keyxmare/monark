<?php

declare(strict_types=1);

namespace App\History\Domain\Model;

enum SnapshotSource: string
{
    case Live = 'live';
    case Backfill = 'backfill';
}
