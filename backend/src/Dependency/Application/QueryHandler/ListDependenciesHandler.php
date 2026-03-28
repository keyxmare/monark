<?php

declare(strict_types=1);

namespace App\Dependency\Application\QueryHandler;

use App\Dependency\Application\DTO\DependencyListOutput;
use App\Dependency\Application\DTO\DependencyOutput;
use App\Dependency\Application\Query\ListDependenciesQuery;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Dependency\Domain\Repository\DependencyVersionRepositoryInterface;
use App\Shared\Application\DTO\PaginatedOutput;
use DateTimeInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListDependenciesHandler
{
    public function __construct(
        private DependencyRepositoryInterface $dependencyRepository,
        private DependencyVersionRepositoryInterface $versionRepository,
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

        $dependencies = $this->dependencyRepository->findFiltered($query->page, $query->perPage, $filters);
        $total = $this->dependencyRepository->countFiltered($filters);

        $items = \array_map(
            function ($dependency) {
                $manager = $dependency->getPackageManager();
                $name = $dependency->getName();

                $currentVer = $this->versionRepository->findByNameManagerAndVersion($name, $manager, $dependency->getCurrentVersion());
                $latestVer = $this->versionRepository->findByNameManagerAndVersion($name, $manager, $dependency->getLatestVersion());

                return DependencyOutput::fromEntity(
                    $dependency,
                    $currentVer?->getReleaseDate()?->format(DateTimeInterface::ATOM),
                    $latestVer?->getReleaseDate()?->format(DateTimeInterface::ATOM),
                );
            },
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
