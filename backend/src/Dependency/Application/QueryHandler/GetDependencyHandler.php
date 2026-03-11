<?php

declare(strict_types=1);

namespace App\Dependency\Application\QueryHandler;

use App\Dependency\Application\DTO\DependencyOutput;
use App\Dependency\Application\Query\GetDependencyQuery;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
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

        return DependencyOutput::fromEntity($dependency);
    }
}
