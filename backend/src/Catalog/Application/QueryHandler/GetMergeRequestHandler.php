<?php

declare(strict_types=1);

namespace App\Catalog\Application\QueryHandler;

use App\Catalog\Application\DTO\MergeRequestOutput;
use App\Catalog\Application\Query\GetMergeRequestQuery;
use App\Catalog\Domain\Repository\MergeRequestRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetMergeRequestHandler
{
    public function __construct(
        private MergeRequestRepositoryInterface $mergeRequestRepository,
    ) {
    }

    public function __invoke(GetMergeRequestQuery $query): MergeRequestOutput
    {
        $mr = $this->mergeRequestRepository->findById(Uuid::fromString($query->mergeRequestId));

        if ($mr === null) {
            throw NotFoundException::forEntity('MergeRequest', $query->mergeRequestId);
        }

        return MergeRequestOutput::fromEntity($mr);
    }
}
