<?php

declare(strict_types=1);

namespace App\Catalog\Application\QueryHandler;

use App\Catalog\Application\DTO\TechStackOutput;
use App\Catalog\Application\Query\GetTechStackQuery;
use App\Catalog\Domain\Repository\TechStackRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetTechStackHandler
{
    public function __construct(
        private TechStackRepositoryInterface $techStackRepository,
    ) {
    }

    public function __invoke(GetTechStackQuery $query): TechStackOutput
    {
        $techStack = $this->techStackRepository->findById(Uuid::fromString($query->techStackId));
        if ($techStack === null) {
            throw NotFoundException::forEntity('TechStack', $query->techStackId);
        }

        return TechStackOutput::fromEntity($techStack);
    }
}
