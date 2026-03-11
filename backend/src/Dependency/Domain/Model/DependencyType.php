<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Model;

enum DependencyType: string
{
    case Runtime = 'runtime';
    case Dev = 'dev';
}
