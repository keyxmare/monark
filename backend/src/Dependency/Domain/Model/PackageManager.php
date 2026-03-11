<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Model;

enum PackageManager: string
{
    case Composer = 'composer';
    case Npm = 'npm';
    case Pip = 'pip';
}
