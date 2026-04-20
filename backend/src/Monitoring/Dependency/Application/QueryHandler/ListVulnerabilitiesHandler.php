<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Application\QueryHandler;

use App\Hub\Shared\Application\DTO\PaginatedOutput;
use App\Monitoring\Dependency\Application\DTO\VulnerabilityListOutput;
use App\Monitoring\Dependency\Application\Mapper\VulnerabilityMapper;
use App\Monitoring\Dependency\Application\Query\ListVulnerabilitiesQuery;
use App\Monitoring\Dependency\Domain\Repository\VulnerabilityRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListVulnerabilitiesHandler
{
    public function __construct(
        private VulnerabilityRepositoryInterface $vulnerabilityRepository,
    ) {
    }

    public function __invoke(ListVulnerabilitiesQuery $query): VulnerabilityListOutput
    {
        if ($query->dependencyId !== null) {
            $vulnerabilities = $this->vulnerabilityRepository->findByDependencyId(
                Uuid::fromString($query->dependencyId),
            );
            $total = \count($vulnerabilities);
        } else {
            $vulnerabilities = $this->vulnerabilityRepository->findAll($query->page, $query->perPage);
            $total = $this->vulnerabilityRepository->count();
        }

        $items = \array_map(
            static fn ($vulnerability) => VulnerabilityMapper::toOutput($vulnerability),
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
