<?php

declare(strict_types=1);

namespace App\Assessment\Application\Query;

final readonly class ListQuestionsQuery
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
        public ?string $quizId = null,
    ) {
    }
}
