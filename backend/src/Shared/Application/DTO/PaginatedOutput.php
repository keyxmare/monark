<?php

declare(strict_types=1);

namespace App\Shared\Application\DTO;

final readonly class PaginatedOutput
{
    /** @param list<mixed> $items */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {
    }

    public function totalPages(): int
    {
        return (int) \ceil($this->total / \max(1, $this->perPage));
    }

    /** @return array{items: array<mixed>, total: int, page: int, per_page: int, total_pages: int} */
    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'total' => $this->total,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'total_pages' => $this->totalPages(),
        ];
    }
}
