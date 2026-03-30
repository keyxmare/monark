<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command;

use App\Catalog\Application\DTO\CreateLanguageInput;

final readonly class CreateLanguageCommand
{
    public function __construct(public CreateLanguageInput $input)
    {
    }
}
