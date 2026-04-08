<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command;

use App\Catalog\Application\DTO\CreateFrameworkInput;

final readonly class CreateFrameworkCommand
{
    public function __construct(public CreateFrameworkInput $input)
    {
    }
}
