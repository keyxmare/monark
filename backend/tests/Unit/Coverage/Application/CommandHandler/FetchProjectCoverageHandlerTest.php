<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Model\ProviderType;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Coverage\Application\Command\FetchProjectCoverageCommand;
use App\Coverage\Application\CommandHandler\FetchProjectCoverageHandler;
use App\Coverage\Domain\Model\CoverageSnapshot;
use App\Coverage\Domain\Port\CoverageProviderInterface;
use App\Coverage\Domain\Repository\CoverageSnapshotRepositoryInterface;
use App\Coverage\Domain\ValueObject\CoverageResult;
use App\Coverage\Infrastructure\Provider\CoverageProviderRegistry;
use App\Shared\Domain\Event\ProjectCoverageFetchedEvent;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\ProviderFactory;

function makeFetchProject(
    ?Project $project = null,
    ?CoverageResult $coverageResult = null,
    bool $hasProvider = true,
    bool $resolveProvider = true,
): array {
    $provider = ProviderFactory::create(type: ProviderType::GitLab);

    $builtProject = $project ?? Project::create(
        name: 'My Project',
        slug: 'my-project',
        description: null,
        repositoryUrl: 'https://gitlab.example.com/test.git',
        defaultBranch: 'main',
        visibility: ProjectVisibility::Private,
        ownerId: Uuid::v7(),
        provider: $hasProvider ? $provider : null,
        externalId: '42',
    );

    $projectRepo = new class ($builtProject) implements ProjectRepositoryInterface {
        public function __construct(private readonly ?Project $project)
        {
        }
        public function findById(Uuid $id): ?Project
        {
            return $this->project;
        }
        public function findBySlug(string $slug): ?Project
        {
            return null;
        }
        public function findByExternalIdAndProvider(string $externalId, Uuid $providerId): ?Project
        {
            return null;
        }
        public function findExternalIdMapByProvider(Uuid $providerId): array
        {
            return [];
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function findByProviderId(Uuid $providerId): array
        {
            return [];
        }
        public function findAllWithProvider(): array
        {
            return [];
        }
        public function count(): int
        {
            return 0;
        }
        public function save(Project $project): void
        {
        }
        public function delete(Project $project): void
        {
        }
    };

    $coverageProvider = new class ($coverageResult) implements CoverageProviderInterface {
        public function __construct(private readonly ?CoverageResult $result)
        {
        }
        public function supports(ProviderType $type): bool
        {
            return true;
        }
        public function fetchCoverage(Project $project): ?CoverageResult
        {
            return $this->result;
        }
    };

    $registry = new CoverageProviderRegistry($resolveProvider ? [$coverageProvider] : []);

    $snapshotRepo = new class () implements CoverageSnapshotRepositoryInterface {
        /** @var list<CoverageSnapshot> */
        public array $saved = [];
        public function save(CoverageSnapshot $snapshot): void
        {
            $this->saved[] = $snapshot;
        }
        public function findLatestByProject(Uuid $projectId): ?CoverageSnapshot
        {
            return null;
        }
        public function findAllByProject(Uuid $projectId, int $limit = 50): array
        {
            return [];
        }
        public function findLatestPerProject(): array
        {
            return [];
        }
        public function findPreviousPerProject(): array
        {
            return [];
        }
    };

    return [$builtProject, $projectRepo, $registry, $snapshotRepo];
}

describe('FetchProjectCoverageHandler', function () {
    it('fetches coverage, persists snapshot, and dispatches event with percent', function () {
        $coverageResult = new CoverageResult(
            coveragePercent: 87.5,
            commitHash: 'abc123def456abc123def456abc123def456abcd',
            ref: 'main',
            pipelineId: '999',
        );

        [$project, $projectRepo, $registry, $snapshotRepo] = \makeFetchProject(coverageResult: $coverageResult);

        $syncId = Uuid::v7()->toRfc4122();
        $eventBus = $this->createMock(MessageBusInterface::class);
        $eventBus->expects($this->once())->method('dispatch')
            ->with($this->callback(
                fn ($event) => $event instanceof ProjectCoverageFetchedEvent
                && $event->projectId === $project->getId()->toRfc4122()
                && $event->syncId === $syncId
                && $event->projectName === 'My Project'
                && $event->coveragePercent === 87.5
            ))
            ->willReturn(new Envelope(new \stdClass()));

        $handler = new FetchProjectCoverageHandler($projectRepo, $registry, $snapshotRepo, $eventBus);
        $handler(new FetchProjectCoverageCommand($project->getId()->toRfc4122(), $syncId));

        expect($snapshotRepo->saved)->toHaveCount(1);
        expect($snapshotRepo->saved[0]->getCoveragePercent())->toBe(87.5);
        expect($snapshotRepo->saved[0]->getCommitHash())->toBe('abc123def456abc123def456abc123def456abcd');
    });

    it('dispatches event with null coverage when project has no provider', function () {
        [$project, $projectRepo, $registry, $snapshotRepo] = \makeFetchProject(hasProvider: false);

        $syncId = Uuid::v7()->toRfc4122();
        $eventBus = $this->createMock(MessageBusInterface::class);
        $eventBus->expects($this->once())->method('dispatch')
            ->with($this->callback(
                fn ($event) => $event instanceof ProjectCoverageFetchedEvent
                && $event->coveragePercent === null
            ))
            ->willReturn(new Envelope(new \stdClass()));

        $handler = new FetchProjectCoverageHandler($projectRepo, $registry, $snapshotRepo, $eventBus);
        $handler(new FetchProjectCoverageCommand($project->getId()->toRfc4122(), $syncId));

        expect($snapshotRepo->saved)->toHaveCount(0);
    });

    it('dispatches event with null coverage when fetchCoverage returns null', function () {
        [$project, $projectRepo, $registry, $snapshotRepo] = \makeFetchProject(
            coverageResult: null,
            hasProvider: true,
            resolveProvider: true,
        );

        $syncId = Uuid::v7()->toRfc4122();
        $eventBus = $this->createMock(MessageBusInterface::class);
        $eventBus->expects($this->once())->method('dispatch')
            ->with($this->callback(
                fn ($event) => $event instanceof ProjectCoverageFetchedEvent
                && $event->coveragePercent === null
            ))
            ->willReturn(new Envelope(new \stdClass()));

        $handler = new FetchProjectCoverageHandler($projectRepo, $registry, $snapshotRepo, $eventBus);
        $handler(new FetchProjectCoverageCommand($project->getId()->toRfc4122(), $syncId));

        expect($snapshotRepo->saved)->toHaveCount(0);
    });

    it('persists jobs from CoverageResult into snapshot', function () {
        $coverageResult = new CoverageResult(
            coveragePercent: 87.5,
            commitHash: 'abc123def456abc123def456abc123def456abcd',
            ref: 'main',
            pipelineId: '999',
            jobs: [
                new \App\Coverage\Domain\ValueObject\JobCoverage('backend', 92.0),
                new \App\Coverage\Domain\ValueObject\JobCoverage('frontend', 78.5),
            ],
        );

        [$project, $projectRepo, $registry, $snapshotRepo] = \makeFetchProject(coverageResult: $coverageResult);

        $syncId = Uuid::v7()->toRfc4122();
        $eventBus = $this->createMock(MessageBusInterface::class);
        $eventBus->expects($this->once())->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $handler = new FetchProjectCoverageHandler($projectRepo, $registry, $snapshotRepo, $eventBus);
        $handler(new FetchProjectCoverageCommand($project->getId()->toRfc4122(), $syncId));

        expect($snapshotRepo->saved)->toHaveCount(1);
        expect($snapshotRepo->saved[0]->getJobs())->toBe([
            ['name' => 'backend', 'percent' => 92.0],
            ['name' => 'frontend', 'percent' => 78.5],
        ]);
    });

    it('does not save snapshot when coverage is null', function () {
        [$project, $projectRepo, $registry, $snapshotRepo] = \makeFetchProject(resolveProvider: false);

        $syncId = Uuid::v7()->toRfc4122();
        $eventBus = $this->createMock(MessageBusInterface::class);
        $eventBus->expects($this->once())->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));

        $handler = new FetchProjectCoverageHandler($projectRepo, $registry, $snapshotRepo, $eventBus);
        $handler(new FetchProjectCoverageCommand($project->getId()->toRfc4122(), $syncId));

        expect($snapshotRepo->saved)->toHaveCount(0);
    });
});
