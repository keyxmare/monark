<?php

declare(strict_types=1);

namespace App\Assessment\Application\Query;

final readonly class GetAnswerQuery
{
    public function __construct(
        public string $answerId,
    ) {
    }
}
