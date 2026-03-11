<?php

declare(strict_types=1);

namespace App\Identity\Application\QueryHandler;

use App\Identity\Application\DTO\TeamListOutput;
use App\Identity\Application\DTO\TeamOutput;
use App\Identity\Application\Query\ListTeamsQuery;
use App\Identity\Domain\Repository\TeamRepositoryInterface;
use App\Shared\Application\DTO\PaginatedOutput;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListTeamsHandler
{
    public function __construct(
        private TeamRepositoryInterface $teamRepository,
    ) {
    }

    public function __invoke(ListTeamsQuery $query): TeamListOutput
    {
        $teams = $this->teamRepository->findAll($query->page, $query->perPage);
        $total = $this->teamRepository->count();

        $items = \array_map(
            static fn ($team) => TeamOutput::fromEntity($team),
            $teams,
        );

        return new TeamListOutput(
            pagination: new PaginatedOutput(
                items: $items,
                total: $total,
                page: $query->page,
                perPage: $query->perPage,
            ),
        );
    }
}
