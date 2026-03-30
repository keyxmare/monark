<?php

declare(strict_types=1);

namespace App\Catalog\Application\Query;

final readonly class ListLanguagesQuery
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
        public ?string $projectId = null,
    ) {
    }
}
