<?php

declare(strict_types=1);

namespace App\Catalog\Application\QueryHandler;

use App\Catalog\Application\DTO\MergeRequestListOutput;
use App\Catalog\Application\DTO\MergeRequestOutput;
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
        $status = $query->status !== null ? MergeRequestStatus::tryFrom($query->status) : null;

        $mergeRequests = $this->mergeRequestRepository->findByProjectId(
            $projectId,
            $query->page,
            $query->perPage,
            $status,
            $query->author,
        );

        $total = $this->mergeRequestRepository->countByProjectId($projectId, $status, $query->author);

        $items = \array_map(
            static fn ($mr) => MergeRequestOutput::fromEntity($mr),
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
}
