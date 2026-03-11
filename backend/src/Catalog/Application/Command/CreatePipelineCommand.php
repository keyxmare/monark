<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command;

use App\Catalog\Application\DTO\CreatePipelineInput;

final readonly class CreatePipelineCommand
{
    public function __construct(
        public CreatePipelineInput $input,
    ) {
    }
}
