<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Adapter;

use App\Catalog\Domain\Model\MergeRequestStatus;
use App\Catalog\Domain\Repository\MergeRequestRepositoryInterface;
use App\Shared\Domain\DTO\MergeRequestReadDTO;
use App\Shared\Domain\Port\MergeRequestReaderPort;
use Symfony\Component\Uid\Uuid;

final readonly class MergeRequestReaderAdapter implements MergeRequestReaderPort
{
    public function __construct(
        private MergeRequestRepositoryInterface $mergeRequestRepository,
    ) {
    }

    /** @return list<MergeRequestReadDTO> */
    public function findActiveByProjectId(Uuid $projectId): array
    {
        $mergeRequests = $this->mergeRequestRepository->findByProjectId(
            $projectId,
            1,
            100,
            [MergeRequestStatus::Open, MergeRequestStatus::Draft],
        );

        return \array_map(
            fn ($mr) => new MergeRequestReadDTO(
                externalId: $mr->getExternalId(),
                title: $mr->getTitle(),
                author: $mr->getAuthor(),
                status: $mr->getStatus()->value,
                url: $mr->getUrl(),
                updatedAt: $mr->getUpdatedAt(),
            ),
            $mergeRequests,
        );
    }
}
