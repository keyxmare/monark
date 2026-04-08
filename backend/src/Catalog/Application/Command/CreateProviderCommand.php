<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command;

use App\Catalog\Application\DTO\CreateProviderInput;

final readonly class CreateProviderCommand
{
    public function __construct(
        public CreateProviderInput $input,
    ) {
    }
}
