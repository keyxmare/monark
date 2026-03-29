<?php

declare(strict_types=1);

use App\Activity\Application\DTO\BuildMetricListOutput;
use App\Activity\Application\Query\ListBuildMetricsQuery;
use App\Activity\Application\QueryHandler\ListBuildMetricsHandler;
use App\Activity\Domain\Model\BuildMetric;
use App\Activity\Domain\Repository\BuildMetricRepositoryInterface;
use Symfony\Component\Uid\Uuid;

describe('ListBuildMetricsHandler', function () {
    it('returns paginated build metrics', function () {
        $projectId = Uuid::v7();
        $metric = BuildMetric::create(
            projectId: $projectId,
            commitSha: 'abc123def456',
            ref: 'main',
            backendCoverage: 85.5,
            frontendCoverage: 72.0,
            mutationScore: 68.3,
        );

        $repo = $this->createMock(BuildMetricRepositoryInterface::class);
        $repo->method('findByProjectId')->willReturn([$metric]);
        $repo->method('countByProjectId')->willReturn(1);

        $handler = new ListBuildMetricsHandler($repo);
        $result = $handler(new ListBuildMetricsQuery($projectId->toRfc4122()));

        expect($result)->toBeInstanceOf(BuildMetricListOutput::class);
        expect($result->data->items)->toHaveCount(1);
        expect($result->data->total)->toBe(1);
        expect($result->data->page)->toBe(1);
        expect($result->data->perPage)->toBe(20);
    });

    it('returns empty list when no metrics exist', function () {
        $projectId = Uuid::v7();

        $repo = $this->createMock(BuildMetricRepositoryInterface::class);
        $repo->method('findByProjectId')->willReturn([]);
        $repo->method('countByProjectId')->willReturn(0);

        $handler = new ListBuildMetricsHandler($repo);
        $result = $handler(new ListBuildMetricsQuery($projectId->toRfc4122()));

        expect($result)->toBeInstanceOf(BuildMetricListOutput::class);
        expect($result->data->items)->toBeEmpty();
        expect($result->data->total)->toBe(0);
    });
});
