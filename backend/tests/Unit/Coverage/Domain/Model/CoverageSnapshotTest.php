<?php

declare(strict_types=1);

use App\Coverage\Domain\Model\CoverageSnapshot;
use App\Coverage\Domain\Model\CoverageSource;
use Symfony\Component\Uid\Uuid;

describe('CoverageSnapshot', function (): void {
    it('creates with valid data', function (): void {
        $projectId = Uuid::v7();
        $snapshot = CoverageSnapshot::create(
            projectId: $projectId,
            commitHash: 'a3f21bc4e5d6f7890123456789abcdef01234567',
            coveragePercent: 82.3,
            source: CoverageSource::CiGitlab,
            ref: 'main',
            pipelineId: '12345',
        );

        expect($snapshot->getId())->toBeInstanceOf(Uuid::class)
            ->and($snapshot->getProjectId())->toBe($projectId)
            ->and($snapshot->getCommitHash())->toBe('a3f21bc4e5d6f7890123456789abcdef01234567')
            ->and($snapshot->getCoveragePercent())->toBe(82.3)
            ->and($snapshot->getSource())->toBe(CoverageSource::CiGitlab)
            ->and($snapshot->getRef())->toBe('main')
            ->and($snapshot->getPipelineId())->toBe('12345')
            ->and($snapshot->getCreatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('creates without pipeline id', function (): void {
        $snapshot = CoverageSnapshot::create(
            projectId: Uuid::v7(),
            commitHash: 'a3f21bc4e5d6f7890123456789abcdef01234567',
            coveragePercent: 64.7,
            source: CoverageSource::CiGithub,
            ref: 'main',
        );

        expect($snapshot->getPipelineId())->toBeNull();
    });

    it('rejects negative coverage', function (): void {
        CoverageSnapshot::create(
            projectId: Uuid::v7(),
            commitHash: 'a3f21bc4e5d6f7890123456789abcdef01234567',
            coveragePercent: -1.0,
            source: CoverageSource::CiGitlab,
            ref: 'main',
        );
    })->throws(\InvalidArgumentException::class);

    it('rejects coverage above 100', function (): void {
        CoverageSnapshot::create(
            projectId: Uuid::v7(),
            commitHash: 'a3f21bc4e5d6f7890123456789abcdef01234567',
            coveragePercent: 100.1,
            source: CoverageSource::CiGitlab,
            ref: 'main',
        );
    })->throws(\InvalidArgumentException::class);

    it('rejects empty commit hash', function (): void {
        CoverageSnapshot::create(
            projectId: Uuid::v7(),
            commitHash: '',
            coveragePercent: 80.0,
            source: CoverageSource::CiGitlab,
            ref: 'main',
        );
    })->throws(\InvalidArgumentException::class);
});
