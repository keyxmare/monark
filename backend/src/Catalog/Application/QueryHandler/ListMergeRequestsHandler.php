<?php

declare(strict_types=1);

namespace App\Catalog\Application\QueryHandler;

use App\Catalog\Application\DTO\MergeRequestListOutput;
use App\Catalog\Application\Mapper\MergeRequestMapper;
use App\Catalog\Application\Query\ListMergeRequestsQuery;
use App\Catalog\Domain\Model\MergeRequestStatus;
use App\Catalog\Domain\Repository\MergeRequestRepositoryInterface;
use App\Shared\Application\DTO\PaginatedOutput;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListMergeRequestsHandler
{
    public function __construct(
        private MergeRequestRepositoryInterface $mergeRequestRepository,
    ) {
    }

    public function __invoke(ListMergeRequestsQuery $query): MergeRequestListOutput
    {
        $projectId = Uuid::fromString($query->projectId);
        $statuses = self::resolveStatuses($query->status);

        $mergeRequests = $this->mergeRequestRepository->findByProjectId(
            $projectId,
            $query->page,
            $query->perPage,
            $statuses,
            $query->author,
        );

        $total = $this->mergeRequestRepository->countByProjectId($projectId, $statuses, $query->author);

        $items = \array_map(
            static fn ($mr) => MergeRequestMapper::toOutput($mr),
            $mergeRequests,
        );

        return new MergeRequestListOutput(
            pagination: new PaginatedOutput(
                items: $items,
                total: $total,
                page: $query->page,
                perPage: $query->perPage,
            ),
        );
    }

    /** @return list<MergeRequestStatus> */
    private static function resolveStatuses(?string $status): array
    {
        if ($status === null || $status === '') {
            return [];
        }

        if ($status === 'active') {
            return [MergeRequestStatus::Open, MergeRequestStatus::Draft];
        }

        $statuses = [];
        foreach (\explode(',', $status) as $s) {
            $resolved = MergeRequestStatus::tryFrom(\trim($s));
            if ($resolved !== null) {
                $statuses[] = $resolved;
            }
        }

        return $statuses;
    }
}
