<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command;

use App\Catalog\Application\DTO\CreateTechStackInput;

final readonly class CreateTechStackCommand
{
    public function __construct(
        public CreateTechStackInput $input,
    ) {
    }
}
