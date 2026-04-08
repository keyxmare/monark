<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Model;

enum ProviderType: string
{
    case GitLab = 'gitlab';
    case GitHub = 'github';
    case Bitbucket = 'bitbucket';
}
