<?php

declare(strict_types=1);

namespace App\Catalog\Application\QueryHandler;

use App\Catalog\Application\DTO\TechStackListOutput;
use App\Catalog\Application\DTO\TechStackOutput;
use App\Catalog\Application\Query\ListTechStacksQuery;
use App\Catalog\Domain\Repository\TechStackRepositoryInterface;
use App\Shared\Application\DTO\PaginatedOutput;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListTechStacksHandler
{
    public function __construct(
        private TechStackRepositoryInterface $techStackRepository,
    ) {
    }

    public function __invoke(ListTechStacksQuery $query): TechStackListOutput
    {
        if ($query->projectId !== null) {
            $projectUuid = Uuid::fromString($query->projectId);
            $techStacks = $this->techStackRepository->findByProjectId($projectUuid, $query->page, $query->perPage);
            $total = $this->techStackRepository->countByProjectId($projectUuid);
        } else {
            $techStacks = $this->techStackRepository->findAll($query->page, $query->perPage);
            $total = $this->techStackRepository->count();
        }

        $items = \array_map(
            static fn ($techStack) => TechStackOutput::fromEntity($techStack),
            $techStacks,
        );

        return new TechStackListOutput(
            pagination: new PaginatedOutput(
                items: $items,
                total: $total,
                page: $query->page,
                perPage: $query->perPage,
            ),
        );
    }
}
