<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\Command;

use App\Monitoring\Catalog\Application\DTO\CreateProviderInput;

final readonly class CreateProviderCommand
{
    public function __construct(
        public CreateProviderInput $input,
    ) {
    }
}
