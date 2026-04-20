<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Domain\Model;

enum ProjectVisibility: string
{
    case Public = 'public';
    case Private = 'private';
}
