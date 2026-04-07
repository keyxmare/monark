<?php

declare(strict_types=1);

namespace App\History\Application\QueryHandler;

use App\History\Application\DTO\DebtTimelinePoint;
use App\History\Application\Query\GetDebtTimelineQuery;
use App\History\Domain\Repository\ProjectDebtSnapshotRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetDebtTimelineHandler
{
    public function __construct(
        private ProjectDebtSnapshotRepositoryInterface $repository,
    ) {
    }

    /** @return list<DebtTimelinePoint> */
    public function __invoke(GetDebtTimelineQuery $query): array
    {
        $snapshots = $this->repository->findByProjectBetween(
            Uuid::fromString($query->projectId),
            $query->from,
            $query->to,
        );

        return \array_map(
            static fn ($snapshot): DebtTimelinePoint => DebtTimelinePoint::fromEntity($snapshot),
            $snapshots,
        );
    }
}
