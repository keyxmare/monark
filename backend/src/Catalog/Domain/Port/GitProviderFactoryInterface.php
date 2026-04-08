<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Port;

use App\Catalog\Domain\Model\Provider;

interface GitProviderFactoryInterface
{
    public function create(Provider $provider): GitProviderInterface;
}
