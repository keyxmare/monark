<?php

declare(strict_types=1);

namespace App\VersionRegistry\Domain\Model;

enum ResolverSource: string
{
    case EndOfLife = 'endoflife';
    case Registry = 'registry';
}
