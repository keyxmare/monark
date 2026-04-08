<?php

declare(strict_types=1);

use App\Activity\Application\DTO\BuildMetricOutput;
use App\Activity\Application\Query\GetLatestBuildMetricQuery;
use App\Activity\Application\QueryHandler\GetLatestBuildMetricHandler;
use App\Activity\Domain\Model\BuildMetric;
use App\Activity\Domain\Repository\BuildMetricRepositoryInterface;
use Symfony\Component\Uid\Uuid;

describe('GetLatestBuildMetricHandler', function () {
    it('returns the latest build metric for a project', function () {
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
        $repo->method('findLatestByProjectId')->willReturn($metric);

        $handler = new GetLatestBuildMetricHandler($repo);
        $result = $handler(new GetLatestBuildMetricQuery($projectId->toRfc4122()));

        expect($result)->toBeInstanceOf(BuildMetricOutput::class);
        expect($result->projectId)->toBe($projectId->toRfc4122());
        expect($result->commitSha)->toBe('abc123def456');
        expect($result->ref)->toBe('main');
        expect($result->backendCoverage)->toBe(85.5);
        expect($result->frontendCoverage)->toBe(72.0);
        expect($result->mutationScore)->toBe(68.3);
    });

    it('returns null when no build metric exists', function () {
        $projectId = Uuid::v7();

        $repo = $this->createMock(BuildMetricRepositoryInterface::class);
        $repo->method('findLatestByProjectId')->willReturn(null);

        $handler = new GetLatestBuildMetricHandler($repo);
        $result = $handler(new GetLatestBuildMetricQuery($projectId->toRfc4122()));

        expect($result)->toBeNull();
    });
});
