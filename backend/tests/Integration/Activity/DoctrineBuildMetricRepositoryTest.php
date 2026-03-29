<?php

declare(strict_types=1);

use App\Activity\Domain\Model\BuildMetric;
use App\Activity\Domain\Repository\BuildMetricRepositoryInterface;
use App\Tests\Helpers\DatabaseHelper;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(BuildMetricRepositoryInterface::class);
});

function createBuildMetric(
    ?Uuid $projectId = null,
    string $commitSha = 'abc1234567890abcdef1234567890abcdef12345',
    string $ref = 'refs/heads/main',
    ?float $backendCoverage = 85.5,
    ?float $frontendCoverage = 72.0,
    ?float $mutationScore = 80.0,
): BuildMetric {
    return BuildMetric::create(
        projectId: $projectId ?? Uuid::v7(),
        commitSha: $commitSha,
        ref: $ref,
        backendCoverage: $backendCoverage,
        frontendCoverage: $frontendCoverage,
        mutationScore: $mutationScore,
    );
}

describe('DoctrineBuildMetricRepository', function () {
    it('saves and finds a build metric by id', function () {
        $metric = \createBuildMetric();
        $this->repo->save($metric);

        $found = $this->repo->findById($metric->getId());

        expect($found)->not->toBeNull();
        expect($found->getCommitSha())->toBe('abc1234567890abcdef1234567890abcdef12345');
        expect($found->getRef())->toBe('refs/heads/main');
        expect($found->getBackendCoverage())->toBe(85.5);
        expect($found->getFrontendCoverage())->toBe(72.0);
        expect($found->getMutationScore())->toBe(80.0);
    });

    it('returns null for unknown id', function () {
        expect($this->repo->findById(Uuid::v7()))->toBeNull();
    });

    it('finds build metrics by project id with pagination', function () {
        $projectId = Uuid::v7();

        for ($i = 0; $i < 5; $i++) {
            $this->repo->save(\createBuildMetric(
                projectId: $projectId,
                commitSha: \str_repeat(\dechex($i), 40),
            ));
        }
        $this->repo->save(\createBuildMetric(commitSha: \str_repeat('f', 40)));

        $page1 = $this->repo->findByProjectId($projectId, page: 1, perPage: 3);
        expect($page1)->toHaveCount(3);

        $page2 = $this->repo->findByProjectId($projectId, page: 2, perPage: 3);
        expect($page2)->toHaveCount(2);
    });

    it('counts build metrics by project id', function () {
        $projectId = Uuid::v7();

        $this->repo->save(\createBuildMetric(projectId: $projectId, commitSha: \str_repeat('a', 40)));
        $this->repo->save(\createBuildMetric(projectId: $projectId, commitSha: \str_repeat('b', 40)));
        $this->repo->save(\createBuildMetric(commitSha: \str_repeat('c', 40)));

        expect($this->repo->countByProjectId($projectId))->toBe(2);
    });

    it('finds latest build metric by project id', function () {
        $projectId = Uuid::v7();

        $first = \createBuildMetric(projectId: $projectId, commitSha: \str_repeat('a', 40));
        $this->repo->save($first);

        $latest = \createBuildMetric(projectId: $projectId, commitSha: \str_repeat('b', 40));
        $this->repo->save($latest);

        $found = $this->repo->findLatestByProjectId($projectId);

        expect($found)->not->toBeNull();
        expect($found->getCommitSha())->toBe(\str_repeat('b', 40));
    });

    it('returns null when no build metrics for project', function () {
        expect($this->repo->findLatestByProjectId(Uuid::v7()))->toBeNull();
    });
});
