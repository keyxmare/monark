<?php

declare(strict_types=1);

namespace App\History\Domain\Model;

enum GapType: string
{
    case None = 'none';
    case Patch = 'patch';
    case Minor = 'minor';
    case Major = 'major';
    case Unknown = 'unknown';
}
