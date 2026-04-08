<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Model;

enum ProjectVisibility: string
{
    case Public = 'public';
    case Private = 'private';
}
