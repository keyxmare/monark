<?php

declare(strict_types=1);

namespace App\Monitoring\Dependency\Application\QueryHandler;

use App\Hub\Shared\Domain\Exception\NotFoundException;
use App\Monitoring\Dependency\Application\DTO\DependencyOutput;
use App\Monitoring\Dependency\Application\Mapper\DependencyMapper;
use App\Monitoring\Dependency\Application\Query\GetDependencyQuery;
use App\Monitoring\Dependency\Domain\Repository\DependencyRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetDependencyHandler
{
    public function __construct(
        private DependencyRepositoryInterface $dependencyRepository,
    ) {
    }

    public function __invoke(GetDependencyQuery $query): DependencyOutput
    {
        $dependency = $this->dependencyRepository->findById(Uuid::fromString($query->dependencyId));
        if ($dependency === null) {
            throw NotFoundException::forEntity('Dependency', $query->dependencyId);
        }

        return DependencyMapper::toOutput($dependency);
    }
}
