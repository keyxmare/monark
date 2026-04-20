<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\Query;

final readonly class GetProviderQuery
{
    public function __construct(
        public string $providerId,
    ) {
    }
}
