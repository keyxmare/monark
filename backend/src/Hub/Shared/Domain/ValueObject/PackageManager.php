<?php

declare(strict_types=1);

namespace App\Hub\Shared\Domain\ValueObject;

enum PackageManager: string
{
    case Composer = 'composer';
    case Npm = 'npm';
    case Pip = 'pip';
}
