<?php

declare(strict_types=1);

namespace App\Catalog\Application\QueryHandler;

use App\Catalog\Application\DTO\ProjectOutput;
use App\Catalog\Application\Mapper\ProjectMapper;
use App\Catalog\Application\Query\GetProjectQuery;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetProjectHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        #[Autowire(service: 'cache.query')]
        private TagAwareCacheInterface $cache,
    ) {
    }

    public function __invoke(GetProjectQuery $query): ProjectOutput
    {
        $cacheKey = \sprintf('project_%s', $query->projectId);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($query): ProjectOutput {
            $item->expiresAfter(300);
            $item->tag(['projects', \sprintf('project_%s', $query->projectId)]);

            $project = $this->projectRepository->findById(Uuid::fromString($query->projectId));
            if ($project === null) {
                throw NotFoundException::forEntity('Project', $query->projectId);
            }

            return ProjectMapper::toOutput($project);
        });
    }
}
