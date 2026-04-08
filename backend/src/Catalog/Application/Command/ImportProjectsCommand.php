<?php

declare(strict_types=1);

namespace App\Catalog\Application\Command;

use App\Catalog\Application\DTO\ImportProjectsInput;

final readonly class ImportProjectsCommand
{
    public function __construct(
        public string $providerId,
        public ImportProjectsInput $input,
        public string $ownerId,
    ) {
    }
}
