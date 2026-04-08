<?php

declare(strict_types=1);

use App\Shared\Application\DTO\PaginatedOutput;

describe('PaginatedOutput', function () {
    it('calculates total pages correctly', function () {
        $output = new PaginatedOutput(items: ['a', 'b', 'c'], total: 25, page: 1, perPage: 10);

        expect($output->totalPages())->toBe(3);
        expect($output->items)->toBe(['a', 'b', 'c']);
        expect($output->total)->toBe(25);
        expect($output->page)->toBe(1);
        expect($output->perPage)->toBe(10);
    });

    it('rounds up total pages', function () {
        $output = new PaginatedOutput(items: [], total: 11, page: 1, perPage: 10);

        expect($output->totalPages())->toBe(2);
    });

    it('handles exact division', function () {
        $output = new PaginatedOutput(items: [], total: 20, page: 1, perPage: 10);

        expect($output->totalPages())->toBe(2);
    });

    it('handles zero total', function () {
        $output = new PaginatedOutput(items: [], total: 0, page: 1, perPage: 10);

        expect($output->totalPages())->toBe(0);
    });

    it('prevents division by zero with perPage=0', function () {
        $output = new PaginatedOutput(items: [], total: 10, page: 1, perPage: 0);

        expect($output->totalPages())->toBe(10);
    });

    it('serializes to array with total_pages', function () {
        $output = new PaginatedOutput(items: ['x'], total: 5, page: 2, perPage: 3);
        $arr = $output->toArray();

        expect($arr['items'])->toBe(['x']);
        expect($arr['total'])->toBe(5);
        expect($arr['page'])->toBe(2);
        expect($arr['per_page'])->toBe(3);
        expect($arr['total_pages'])->toBe(2);
    });
});
