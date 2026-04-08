<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Model;

enum ProviderStatus: string
{
    case Pending = 'pending';
    case Connected = 'connected';
    case Error = 'error';
}
