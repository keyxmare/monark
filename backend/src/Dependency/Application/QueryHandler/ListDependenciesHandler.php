<?php

declare(strict_types=1);

namespace App\Dependency\Application\QueryHandler;

use App\Dependency\Application\DTO\DependencyListOutput;
use App\Dependency\Application\DTO\DependencyOutput;
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
        $dependencies = $this->dependencyRepository->findAll($query->page, $query->perPage);
        $total = $this->dependencyRepository->count();

        $items = \array_map(
            static fn ($dependency) => DependencyOutput::fromEntity($dependency),
            $dependencies,
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
