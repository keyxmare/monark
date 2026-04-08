<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command;

final readonly class DeleteFrameworkCommand
{
    public function __construct(public string $id)
    {
    }
}
