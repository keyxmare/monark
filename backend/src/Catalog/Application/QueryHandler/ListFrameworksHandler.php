<?php

declare(strict_types=1);

namespace App\Catalog\Application\QueryHandler;

use App\Catalog\Application\DTO\FrameworkOutput;
use App\Catalog\Application\Mapper\FrameworkMapper;
use App\Catalog\Application\Query\ListFrameworksQuery;
use App\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListFrameworksHandler
{
    public function __construct(private FrameworkRepositoryInterface $frameworkRepository)
    {
    }

    /** @return list<FrameworkOutput> */
    public function __invoke(ListFrameworksQuery $query): array
    {
        $frameworks = $query->projectId !== null
            ? $this->frameworkRepository->findByProjectId(Uuid::fromString($query->projectId))
            : $this->frameworkRepository->findAll();

        return \array_map(FrameworkMapper::toOutput(...), $frameworks);
    }
}
