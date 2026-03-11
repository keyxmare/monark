<?php

declare(strict_types=1);

namespace App\Catalog\Application\QueryHandler;

use App\Catalog\Application\DTO\ProjectOutput;
use App\Catalog\Application\Query\GetProjectQuery;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetProjectHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
    ) {
    }

    public function __invoke(GetProjectQuery $query): ProjectOutput
    {
        $project = $this->projectRepository->findById(Uuid::fromString($query->projectId));
        if ($project === null) {
            throw NotFoundException::forEntity('Project', $query->projectId);
        }

        return ProjectOutput::fromEntity($project);
    }
}
