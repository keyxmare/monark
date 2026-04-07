<?php

declare(strict_types=1);

namespace App\History\Application\CommandHandler;

use App\History\Application\Command\RecordProjectSnapshotCommand;
use App\History\Domain\Model\DependencySnapshot;
use App\History\Domain\Model\GapType;
use App\History\Domain\Model\ProjectDebtSnapshot;
use App\History\Domain\Port\HistoricalVersionResolverInterface;
use App\History\Domain\Repository\ProjectDebtSnapshotRepositoryInterface;
use App\History\Domain\Service\DebtScoreCalculator;
use App\Shared\Domain\DTO\DetectedDependency;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class RecordProjectSnapshotHandler
{
    public function __construct(
        private ProjectDebtSnapshotRepositoryInterface $repository,
        private HistoricalVersionResolverInterface $versionResolver,
        private DebtScoreCalculator $scoreCalculator,
    ) {
    }

    public function __invoke(RecordProjectSnapshotCommand $command): ProjectDebtSnapshot
    {
        $projectId = Uuid::fromString($command->projectId);

        $existing = $this->repository->findByProjectAndCommit($projectId, $command->commitSha);
        if ($existing !== null) {
            return $existing;
        }

        $deps = $command->scanResult->dependencies;
        $resolved = [];
        $outdated = 0;
        $major = 0;
        $minor = 0;
        $patch = 0;
        $ltsGap = 0;

        foreach ($deps as $dep) {
            $r = $this->versionResolver->resolve($dep->name, $dep->packageManager, $command->snapshotDate);
            $gap = $this->scoreCalculator->determineGapType($dep->currentVersion, $r->latestVersion);

            if ($gap === GapType::Major) {
                ++$outdated;
                ++$major;
            } elseif ($gap === GapType::Minor) {
                ++$outdated;
                ++$minor;
            } elseif ($gap === GapType::Patch) {
                ++$outdated;
                ++$patch;
            }

            if ($r->ltsVersion !== null && $dep->currentVersion !== $r->ltsVersion) {
                ++$ltsGap;
            }

            $resolved[] = ['dep' => $dep, 'resolved' => $r, 'gap' => $gap];
        }

        $score = $this->scoreCalculator->score(
            totalDeps: \count($deps),
            major: $major,
            minor: $minor,
            patch: $patch,
            vulnerable: 0,
            ltsGap: $ltsGap,
        );

        $snapshot = ProjectDebtSnapshot::create(
            projectId: $projectId,
            commitSha: $command->commitSha,
            snapshotDate: $command->snapshotDate,
            source: $command->source,
            totalDeps: \count($deps),
            outdatedCount: $outdated,
            vulnerableCount: 0,
            majorGapCount: $major,
            minorGapCount: $minor,
            patchGapCount: $patch,
            ltsGapCount: $ltsGap,
            debtScore: $score,
        );

        foreach ($resolved as $entry) {
            /** @var DetectedDependency $dep */
            $dep = $entry['dep'];
            $depSnap = new DependencySnapshot(
                debtSnapshot: $snapshot,
                name: $dep->name,
                packageManager: $dep->packageManager,
                type: $dep->type,
                currentVersion: $dep->currentVersion,
                latestVersionAtDate: $entry['resolved']->latestVersion,
                ltsVersionAtDate: $entry['resolved']->ltsVersion,
                isOutdated: $entry['gap'] !== GapType::None && $entry['gap'] !== GapType::Unknown,
                gapType: $entry['gap'],
            );
            $snapshot->attachDependency($depSnap);
        }

        $this->repository->save($snapshot);

        return $snapshot;
    }
}
