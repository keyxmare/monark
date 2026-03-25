<?php

declare(strict_types=1);

namespace App\Dependency\Application\QueryHandler;

use App\Dependency\Application\DTO\DependencyStatsOutput;
use App\Dependency\Application\Query\GetDependencyStatsQuery;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetDependencyStatsHandler
{
    public function __construct(
        private DependencyRepositoryInterface $dependencyRepository,
    ) {
    }

    public function __invoke(GetDependencyStatsQuery $query): DependencyStatsOutput
    {
        $filters = \array_filter([
            'projectId' => $query->projectId,
            'packageManager' => $query->packageManager,
            'type' => $query->type,
        ], static fn ($v) => $v !== null && $v !== '');

        $stats = $this->dependencyRepository->getStats($filters);

        return new DependencyStatsOutput(
            total: $stats['total'],
            upToDate: $stats['total'] - $stats['outdated'],
            outdated: $stats['outdated'],
            totalVulnerabilities: $stats['totalVulnerabilities'],
        );
    }
}
