<?php

declare(strict_types=1);

use App\History\Application\Command\RecordProjectSnapshotCommand;
use App\History\Application\CommandHandler\RecordProjectSnapshotHandler;
use App\History\Domain\DTO\ResolvedHistoricalVersion;
use App\History\Domain\Model\GapType;
use App\History\Domain\Model\ProjectDebtSnapshot;
use App\History\Domain\Model\SnapshotSource;
use App\History\Domain\Port\HistoricalVersionResolverInterface;
use App\History\Domain\Repository\ProjectDebtSnapshotRepositoryInterface;
use App\History\Domain\Service\DebtScoreCalculator;
use App\Shared\Domain\DTO\DetectedDependency;
use App\Shared\Domain\DTO\ScanResult;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use Symfony\Component\Uid\Uuid;

function makeHistoryRepoStub(): ProjectDebtSnapshotRepositoryInterface
{
    return new class () implements ProjectDebtSnapshotRepositoryInterface {
        /** @var list<ProjectDebtSnapshot> */
        public array $saved = [];
        public ?ProjectDebtSnapshot $existing = null;

        public function findById(Uuid $id): ?ProjectDebtSnapshot
        {
            return null;
        }

        public function findByProjectAndCommit(Uuid $projectId, string $commitSha): ?ProjectDebtSnapshot
        {
            return $this->existing;
        }

        public function findByProjectBetween(Uuid $projectId, ?\DateTimeImmutable $from, ?\DateTimeImmutable $to): array
        {
            return [];
        }

        public function save(ProjectDebtSnapshot $snapshot): void
        {
            $this->saved[] = $snapshot;
        }
    };
}

function makeHistoryResolverStub(): HistoricalVersionResolverInterface
{
    return new class () implements HistoricalVersionResolverInterface {
        public function resolve(string $productName, ?PackageManager $packageManager, \DateTimeImmutable $at): ResolvedHistoricalVersion
        {
            return match ($productName) {
                'symfony/framework-bundle' => new ResolvedHistoricalVersion('7.0.0', '6.4.0'),
                'vue' => new ResolvedHistoricalVersion('3.5.0', null),
                default => ResolvedHistoricalVersion::empty(),
            };
        }
    };
}

describe('RecordProjectSnapshotHandler', function () {
    it('records a snapshot with computed debt counters', function () {
        $repo = \makeHistoryRepoStub();
        $handler = new RecordProjectSnapshotHandler($repo, \makeHistoryResolverStub(), new DebtScoreCalculator());

        $projectId = Uuid::v7()->toRfc4122();
        $scanResult = new ScanResult(
            stacks: [],
            dependencies: [
                new DetectedDependency('symfony/framework-bundle', '6.0.0', PackageManager::Composer, DependencyType::Runtime),
                new DetectedDependency('vue', '3.5.0', PackageManager::Npm, DependencyType::Runtime),
            ],
        );

        $snapshot = $handler(new RecordProjectSnapshotCommand(
            projectId: $projectId,
            commitSha: 'abc123',
            snapshotDate: new \DateTimeImmutable('2025-06-01'),
            source: SnapshotSource::Backfill,
            scanResult: $scanResult,
        ));

        expect($snapshot)->toBeInstanceOf(ProjectDebtSnapshot::class);
        expect($repo->saved)->toHaveCount(1);
        expect($snapshot->getTotalDeps())->toBe(2);
        expect($snapshot->getOutdatedCount())->toBe(1);
        expect($snapshot->getMajorGapCount())->toBe(1);
        expect($snapshot->getMinorGapCount())->toBe(0);
        expect($snapshot->getLtsGapCount())->toBe(1);
        expect($snapshot->getSource())->toBe(SnapshotSource::Backfill);
        expect($snapshot->getDependencies())->toHaveCount(2);
    });

    it('returns existing snapshot without saving when commit is already recorded', function () {
        $repo = \makeHistoryRepoStub();
        $existing = ProjectDebtSnapshot::create(
            projectId: Uuid::v7(),
            commitSha: 'abc123',
            snapshotDate: new \DateTimeImmutable('2025-06-01'),
            source: SnapshotSource::Backfill,
            totalDeps: 0,
            outdatedCount: 0,
            vulnerableCount: 0,
            majorGapCount: 0,
            minorGapCount: 0,
            patchGapCount: 0,
            ltsGapCount: 0,
            debtScore: 0.0,
        );
        $repo->existing = $existing;

        $handler = new RecordProjectSnapshotHandler($repo, \makeHistoryResolverStub(), new DebtScoreCalculator());

        $result = $handler(new RecordProjectSnapshotCommand(
            projectId: Uuid::v7()->toRfc4122(),
            commitSha: 'abc123',
            snapshotDate: new \DateTimeImmutable('2025-06-01'),
            source: SnapshotSource::Backfill,
            scanResult: new ScanResult(stacks: [], dependencies: []),
        ));

        expect($result)->toBe($existing);
        expect($repo->saved)->toHaveCount(0);
    });

    it('marks gap as none when current equals latest', function () {
        $repo = \makeHistoryRepoStub();
        $handler = new RecordProjectSnapshotHandler($repo, \makeHistoryResolverStub(), new DebtScoreCalculator());

        $snapshot = $handler(new RecordProjectSnapshotCommand(
            projectId: Uuid::v7()->toRfc4122(),
            commitSha: 'def456',
            snapshotDate: new \DateTimeImmutable('2025-06-01'),
            source: SnapshotSource::Live,
            scanResult: new ScanResult(stacks: [], dependencies: [
                new DetectedDependency('vue', '3.5.0', PackageManager::Npm, DependencyType::Runtime),
            ]),
        ));

        expect($snapshot->getOutdatedCount())->toBe(0);
        $first = $snapshot->getDependencies()->first();
        expect($first)->not->toBeFalse();
        expect($first ? $first->getGapType() : null)->toBe(GapType::None);
    });
});
