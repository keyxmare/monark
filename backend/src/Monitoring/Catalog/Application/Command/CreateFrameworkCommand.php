<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\Command;

use App\Monitoring\Catalog\Application\DTO\CreateFrameworkInput;

final readonly class CreateFrameworkCommand
{
    public function __construct(public CreateFrameworkInput $input)
    {
    }
}
