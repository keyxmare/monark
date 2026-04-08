<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command;

use App\Catalog\Application\DTO\UpdateProviderInput;

final readonly class UpdateProviderCommand
{
    public function __construct(
        public string $providerId,
        public UpdateProviderInput $input,
    ) {
    }
}
