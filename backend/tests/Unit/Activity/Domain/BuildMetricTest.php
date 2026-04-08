<?php

declare(strict_types=1);

use App\Activity\Domain\Model\BuildMetric;
use Symfony\Component\Uid\Uuid;

describe('BuildMetric', function () {
    it('creates with all fields', function () {
        $projectId = Uuid::v7();
        $metric = BuildMetric::create(
            projectId: $projectId,
            commitSha: 'abc123def456',
            ref: 'master',
            backendCoverage: 82.6,
            frontendCoverage: 16.37,
            mutationScore: 76.55,
        );

        expect($metric->getId())->toBeInstanceOf(Uuid::class);
        expect($metric->getProjectId()->equals($projectId))->toBeTrue();
        expect($metric->getCommitSha())->toBe('abc123def456');
        expect($metric->getRef())->toBe('master');
        expect($metric->getBackendCoverage())->toBe(82.6);
        expect($metric->getFrontendCoverage())->toBe(16.37);
        expect($metric->getMutationScore())->toBe(76.55);
        expect($metric->getCreatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('creates with nullable coverage fields', function () {
        $metric = BuildMetric::create(
            projectId: Uuid::v7(),
            commitSha: 'deadbeef',
            ref: 'feature/test',
        );

        expect($metric->getBackendCoverage())->toBeNull();
        expect($metric->getFrontendCoverage())->toBeNull();
        expect($metric->getMutationScore())->toBeNull();
    });

    it('creates with partial coverage', function () {
        $metric = BuildMetric::create(
            projectId: Uuid::v7(),
            commitSha: 'cafebabe',
            ref: 'develop',
            backendCoverage: 90.0,
        );

        expect($metric->getBackendCoverage())->toBe(90.0);
        expect($metric->getFrontendCoverage())->toBeNull();
        expect($metric->getMutationScore())->toBeNull();
    });
});
