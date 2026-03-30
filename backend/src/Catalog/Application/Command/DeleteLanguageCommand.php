<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command;

final readonly class DeleteLanguageCommand
{
    public function __construct(public string $id)
    {
    }
}
