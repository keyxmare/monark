<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Model;

use DateTimeImmutable;

final readonly class RemoteCommit
{
    public function __construct(
        public string $sha,
        public DateTimeImmutable $date,
        public string $message,
    ) {
    }
}
