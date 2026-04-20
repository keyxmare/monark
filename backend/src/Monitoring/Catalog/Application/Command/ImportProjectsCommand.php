<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\Command;

use App\Monitoring\Catalog\Application\DTO\ImportProjectsInput;

final readonly class ImportProjectsCommand
{
    public function __construct(
        public string $providerId,
        public ImportProjectsInput $input,
        public string $ownerId,
    ) {
    }
}
