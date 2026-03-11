<?php

declare(strict_types=1);

namespace App\Assessment\Application\QueryHandler;

use App\Assessment\Application\DTO\AttemptListOutput;
use App\Assessment\Application\DTO\AttemptOutput;
use App\Assessment\Application\Query\ListAttemptsQuery;
use App\Assessment\Domain\Repository\AttemptRepositoryInterface;
use App\Shared\Application\DTO\PaginatedOutput;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListAttemptsHandler
{
    public function __construct(
        private AttemptRepositoryInterface $attemptRepository,
    ) {
    }

    public function __invoke(ListAttemptsQuery $query): AttemptListOutput
    {
        $attempts = $this->attemptRepository->findAll($query->page, $query->perPage);
        $total = $this->attemptRepository->count();

        $items = \array_map(
            static fn ($attempt) => AttemptOutput::fromEntity($attempt),
            $attempts,
        );

        return new AttemptListOutput(
            pagination: new PaginatedOutput(
                items: $items,
                total: $total,
                page: $query->page,
                perPage: $query->perPage,
            ),
        );
    }
}
