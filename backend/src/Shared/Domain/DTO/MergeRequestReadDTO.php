<?php

declare(strict_types=1);

namespace App\Shared\Domain\DTO;

use DateTimeImmutable;

final readonly class MergeRequestReadDTO
{
    public function __construct(
        public string $externalId,
        public string $title,
        public string $author,
        public string $status,
        public string $url,
        public DateTimeImmutable $updatedAt,
    ) {
    }
}
