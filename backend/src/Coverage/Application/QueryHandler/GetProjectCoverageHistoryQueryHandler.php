<?php

declare(strict_types=1);

namespace App\Coverage\Application\QueryHandler;

use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Coverage\Application\Query\GetProjectCoverageHistoryQuery;
use App\Coverage\Domain\Repository\CoverageSnapshotRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use DateTimeInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetProjectCoverageHistoryQueryHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private CoverageSnapshotRepositoryInterface $snapshotRepository,
    ) {
    }

    /** @return array{project: array{id: string, name: string, slug: string}, snapshots: list<array{coveragePercent: float, source: string, commitHash: ?string, ref: string, pipelineId: ?string, createdAt: string}>} */
    public function __invoke(GetProjectCoverageHistoryQuery $query): array
    {
        $project = $this->projectRepository->findBySlug($query->projectSlug);

        if ($project === null) {
            throw NotFoundException::forEntity('Project', $query->projectSlug);
        }

        $snapshots = $this->snapshotRepository->findAllByProject($project->getId());

        $snapshotData = \array_map(
            static fn ($snapshot) => [
                'coveragePercent' => $snapshot->getCoveragePercent(),
                'source' => $snapshot->getSource()->value,
                'commitHash' => $snapshot->getCommitHash(),
                'ref' => $snapshot->getRef(),
                'pipelineId' => $snapshot->getPipelineId(),
                'createdAt' => $snapshot->getCreatedAt()->format(DateTimeInterface::ATOM),
            ],
            $snapshots,
        );

        return [
            'project' => [
                'id' => $project->getId()->toRfc4122(),
                'name' => $project->getName(),
                'slug' => $project->getSlug(),
            ],
            'snapshots' => $snapshotData,
        ];
    }
}
