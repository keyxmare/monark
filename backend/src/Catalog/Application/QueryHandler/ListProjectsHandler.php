<?php

declare(strict_types=1);

namespace App\Catalog\Application\QueryHandler;

use App\Catalog\Application\DTO\ProjectListOutput;
use App\Catalog\Application\Mapper\ProjectMapper;
use App\Catalog\Application\Query\ListProjectsQuery;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Application\DTO\PaginatedOutput;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListProjectsHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        #[Autowire(service: 'cache.query')]
        private TagAwareCacheInterface $cache,
    ) {
    }

    public function __invoke(ListProjectsQuery $query): ProjectListOutput
    {
        $cacheKey = \sprintf('projects_list_p%d_pp%d', $query->page, $query->perPage);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($query): ProjectListOutput {
            $item->expiresAfter(300);
            $item->tag(['projects']);

            $projects = $this->projectRepository->findAll($query->page, $query->perPage);
            $total = $this->projectRepository->count();

            $items = \array_map(
                static fn ($project) => ProjectMapper::toOutput($project),
                $projects,
            );

            return new ProjectListOutput(
                pagination: new PaginatedOutput(
                    items: $items,
                    total: $total,
                    page: $query->page,
                    perPage: $query->perPage,
                ),
            );
        });
    }
}
