<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\Command;

final readonly class DeleteProviderCommand
{
    public function __construct(
        public string $providerId,
    ) {
    }
}
