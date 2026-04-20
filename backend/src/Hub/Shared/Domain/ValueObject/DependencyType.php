<?php

declare(strict_types=1);

namespace App\Hub\Shared\Domain\ValueObject;

enum DependencyType: string
{
    case Runtime = 'runtime';
    case Dev = 'dev';
}
