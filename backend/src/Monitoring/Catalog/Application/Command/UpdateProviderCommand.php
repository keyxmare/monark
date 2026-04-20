<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\Command;

use App\Monitoring\Catalog\Application\DTO\UpdateProviderInput;

final readonly class UpdateProviderCommand
{
    public function __construct(
        public string $providerId,
        public UpdateProviderInput $input,
    ) {
    }
}
