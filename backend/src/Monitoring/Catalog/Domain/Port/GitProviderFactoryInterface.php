<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Domain\Port;

use App\Monitoring\Catalog\Domain\Model\Provider;

interface GitProviderFactoryInterface
{
    public function create(Provider $provider): GitProviderInterface;
}
