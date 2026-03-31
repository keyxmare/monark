<?php

declare(strict_types=1);

namespace App\Shared\Domain\DTO;

final readonly class OsvQuery
{
    public function __construct(
        public string $ecosystem,
        public string $name,
        public string $version,
    ) {
    }
}
