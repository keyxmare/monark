<?php

declare(strict_types=1);

namespace App\Catalog\Application\Query;

final readonly class GetFrameworkQuery
{
    public function __construct(public string $id)
    {
    }
}
