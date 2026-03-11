<?php

declare(strict_types=1);

namespace App\Catalog\Application\QueryHandler;

use App\Catalog\Application\DTO\PipelineListOutput;
use App\Catalog\Application\DTO\PipelineOutput;
use App\Catalog\Application\Query\ListPipelinesQuery;
use App\Catalog\Domain\Repository\PipelineRepositoryInterface;
use App\Shared\Application\DTO\PaginatedOutput;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListPipelinesHandler
{
    public function __construct(
        private PipelineRepositoryInterface $pipelineRepository,
    ) {
    }

    public function __invoke(ListPipelinesQuery $query): PipelineListOutput
    {
        if ($query->projectId !== null) {
            $projectUuid = Uuid::fromString($query->projectId);
            $pipelines = $this->pipelineRepository->findByProjectId($projectUuid, $query->page, $query->perPage, $query->ref);
            $total = $this->pipelineRepository->countByProjectId($projectUuid, $query->ref);
        } else {
            $pipelines = $this->pipelineRepository->findAll($query->page, $query->perPage);
            $total = $this->pipelineRepository->count();
        }

        $items = \array_map(
            static fn ($pipeline) => PipelineOutput::fromEntity($pipeline),
            $pipelines,
        );

        return new PipelineListOutput(
            pagination: new PaginatedOutput(
                items: $items,
                total: $total,
                page: $query->page,
                perPage: $query->perPage,
            ),
        );
    }
}
