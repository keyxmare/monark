<?php

declare(strict_types=1);

namespace App\Dependency\Application\QueryHandler;

use App\Dependency\Application\DTO\VulnerabilityListOutput;
use App\Dependency\Application\DTO\VulnerabilityOutput;
use App\Dependency\Application\Query\ListVulnerabilitiesQuery;
use App\Dependency\Domain\Repository\VulnerabilityRepositoryInterface;
use App\Shared\Application\DTO\PaginatedOutput;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListVulnerabilitiesHandler
{
    public function __construct(
        private VulnerabilityRepositoryInterface $vulnerabilityRepository,
    ) {
    }

    public function __invoke(ListVulnerabilitiesQuery $query): VulnerabilityListOutput
    {
        $vulnerabilities = $this->vulnerabilityRepository->findAll($query->page, $query->perPage);
        $total = $this->vulnerabilityRepository->count();

        $items = \array_map(
            static fn ($vulnerability) => VulnerabilityOutput::fromEntity($vulnerability),
            $vulnerabilities,
        );

        return new VulnerabilityListOutput(
            pagination: new PaginatedOutput(
                items: $items,
                total: $total,
                page: $query->page,
                perPage: $query->perPage,
            ),
        );
    }
}
