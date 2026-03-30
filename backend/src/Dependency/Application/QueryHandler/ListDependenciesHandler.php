<?php

declare(strict_types=1);

namespace App\Dependency\Application\QueryHandler;

use App\Dependency\Application\DTO\DependencyListOutput;
use App\Dependency\Application\Mapper\DependencyMapper;
use App\Dependency\Application\Query\ListDependenciesQuery;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Application\DTO\PaginatedOutput;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListDependenciesHandler
{
    public function __construct(
        private DependencyRepositoryInterface $dependencyRepository,
    ) {
    }

    public function __invoke(ListDependenciesQuery $query): DependencyListOutput
    {
        $filters = \array_filter([
            'projectId' => $query->projectId,
            'search' => $query->search,
            'packageManager' => $query->packageManager,
            'type' => $query->type,
            'sort' => $query->sort,
            'sortDir' => $query->sortDir,
        ], static fn ($v) => $v !== null && $v !== '');

        if ($query->isOutdated !== null) {
            $filters['isOutdated'] = $query->isOutdated;
        }

        $rows = $this->dependencyRepository->findFilteredWithVersionDates($query->page, $query->perPage, $filters);
        $total = $this->dependencyRepository->countFiltered($filters);

        $items = \array_map(
            static fn (array $row) => DependencyMapper::toOutput(
                $row['dependency'],
                $row['currentVersionReleasedAt'],
                $row['latestVersionReleasedAt'],
            ),
            $rows,
        );

        return new DependencyListOutput(
            pagination: new PaginatedOutput(
                items: $items,
                total: $total,
                page: $query->page,
                perPage: $query->perPage,
            ),
        );
    }
}
