<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

enum DependencyType: string
{
    case Runtime = 'runtime';
    case Dev = 'dev';
}
