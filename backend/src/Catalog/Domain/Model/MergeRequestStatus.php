<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Model;

enum MergeRequestStatus: string
{
    case Open = 'open';
    case Merged = 'merged';
    case Closed = 'closed';
    case Draft = 'draft';
}
