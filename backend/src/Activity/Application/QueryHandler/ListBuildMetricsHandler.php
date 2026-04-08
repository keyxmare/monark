<?php

declare(strict_types=1);

namespace App\Activity\Application\QueryHandler;

use App\Activity\Application\DTO\BuildMetricListOutput;
use App\Activity\Application\Mapper\BuildMetricMapper;
use App\Activity\Application\Query\ListBuildMetricsQuery;
use App\Activity\Domain\Repository\BuildMetricRepositoryInterface;
use App\Shared\Application\DTO\PaginatedOutput;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListBuildMetricsHandler
{
    public function __construct(
        private BuildMetricRepositoryInterface $buildMetricRepository,
    ) {
    }

    public function __invoke(ListBuildMetricsQuery $query): BuildMetricListOutput
    {
        $projectId = Uuid::fromString($query->projectId);
        $items = $this->buildMetricRepository->findByProjectId($projectId, $query->page, $query->perPage);
        $total = $this->buildMetricRepository->countByProjectId($projectId);

        return new BuildMetricListOutput(
            data: new PaginatedOutput(
                items: \array_map(BuildMetricMapper::toOutput(...), $items),
                total: $total,
                page: $query->page,
                perPage: $query->perPage,
            ),
        );
    }
}
