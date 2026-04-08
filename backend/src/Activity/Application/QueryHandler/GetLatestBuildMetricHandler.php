<?php

declare(strict_types=1);

namespace App\Activity\Application\QueryHandler;

use App\Activity\Application\DTO\BuildMetricOutput;
use App\Activity\Application\Mapper\BuildMetricMapper;
use App\Activity\Application\Query\GetLatestBuildMetricQuery;
use App\Activity\Domain\Repository\BuildMetricRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetLatestBuildMetricHandler
{
    public function __construct(
        private BuildMetricRepositoryInterface $buildMetricRepository,
    ) {
    }

    public function __invoke(GetLatestBuildMetricQuery $query): ?BuildMetricOutput
    {
        $buildMetric = $this->buildMetricRepository->findLatestByProjectId(
            Uuid::fromString($query->projectId),
        );

        return $buildMetric !== null ? BuildMetricMapper::toOutput($buildMetric) : null;
    }
}
