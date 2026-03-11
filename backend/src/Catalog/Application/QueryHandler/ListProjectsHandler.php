<?php

declare(strict_types=1);

namespace App\Catalog\Application\QueryHandler;

use App\Catalog\Application\DTO\ProjectListOutput;
use App\Catalog\Application\DTO\ProjectOutput;
use App\Catalog\Application\Query\ListProjectsQuery;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Application\DTO\PaginatedOutput;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListProjectsHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
    ) {
    }

    public function __invoke(ListProjectsQuery $query): ProjectListOutput
    {
        $projects = $this->projectRepository->findAll($query->page, $query->perPage);
        $total = $this->projectRepository->count();

        $items = \array_map(
            static fn ($project) => ProjectOutput::fromEntity($project),
            $projects,
        );

        return new ProjectListOutput(
            pagination: new PaginatedOutput(
                items: $items,
                total: $total,
                page: $query->page,
                perPage: $query->perPage,
            ),
        );
    }
}
