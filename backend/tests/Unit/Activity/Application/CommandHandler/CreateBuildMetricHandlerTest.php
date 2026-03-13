<?php

declare(strict_types=1);

use App\Activity\Application\Command\CreateBuildMetricCommand;
use App\Activity\Application\CommandHandler\CreateBuildMetricHandler;
use App\Activity\Application\DTO\BuildMetricOutput;
use App\Activity\Application\DTO\CreateBuildMetricInput;
use App\Activity\Domain\Model\BuildMetric;
use App\Activity\Domain\Repository\BuildMetricRepositoryInterface;
use Symfony\Component\Uid\Uuid;

function stubBuildMetricRepo(): BuildMetricRepositoryInterface&stdClass
{
    return new class extends stdClass implements BuildMetricRepositoryInterface {
        /** @var list<BuildMetric> */
        public array $saved = [];

        public function findById(Uuid $id): ?BuildMetric
        {
            return null;
        }

        /** @return list<BuildMetric> */
        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array
        {
            return [];
        }

        public function countByProjectId(Uuid $projectId): int
        {
            return 0;
        }

        public function findLatestByProjectId(Uuid $projectId): ?BuildMetric
        {
            return null;
        }

        public function save(BuildMetric $buildMetric): void
        {
            $this->saved[] = $buildMetric;
        }
    };
}

describe('CreateBuildMetricHandler', function () {
    it('creates and persists a build metric', function () {
        $repo = stubBuildMetricRepo();
        $handler = new CreateBuildMetricHandler($repo);

        $input = new CreateBuildMetricInput(
            commitSha: 'abc123',
            ref: 'master',
            backendCoverage: 82.6,
            frontendCoverage: 16.37,
            mutationScore: 76.55,
        );

        $result = $handler(new CreateBuildMetricCommand(Uuid::v7()->toRfc4122(), $input));

        expect($result)->toBeInstanceOf(BuildMetricOutput::class);
        expect($result->commitSha)->toBe('abc123');
        expect($result->ref)->toBe('master');
        expect($result->backendCoverage)->toBe(82.6);
        expect($result->frontendCoverage)->toBe(16.37);
        expect($result->mutationScore)->toBe(76.55);
        expect($repo->saved)->toHaveCount(1);
    });

    it('creates with nullable fields', function () {
        $repo = stubBuildMetricRepo();
        $handler = new CreateBuildMetricHandler($repo);

        $input = new CreateBuildMetricInput(
            commitSha: 'deadbeef',
            ref: 'feature/x',
        );

        $result = $handler(new CreateBuildMetricCommand(Uuid::v7()->toRfc4122(), $input));

        expect($result->backendCoverage)->toBeNull();
        expect($result->frontendCoverage)->toBeNull();
        expect($result->mutationScore)->toBeNull();
        expect($repo->saved)->toHaveCount(1);
    });
});
