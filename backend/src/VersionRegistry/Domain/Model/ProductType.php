<?php

declare(strict_types=1);

namespace App\VersionRegistry\Domain\Model;

enum ProductType: string
{
    case Language = 'language';
    case Framework = 'framework';
    case Package = 'package';
}
