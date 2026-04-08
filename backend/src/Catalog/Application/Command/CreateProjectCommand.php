<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command;

use App\Catalog\Application\DTO\CreateProjectInput;

final readonly class CreateProjectCommand
{
    public function __construct(
        public CreateProjectInput $input,
    ) {
    }
}
