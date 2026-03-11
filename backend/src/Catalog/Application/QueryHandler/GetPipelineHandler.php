<?php

declare(strict_types=1);

namespace App\Catalog\Application\QueryHandler;

use App\Catalog\Application\DTO\PipelineOutput;
use App\Catalog\Application\Query\GetPipelineQuery;
use App\Catalog\Domain\Repository\PipelineRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetPipelineHandler
{
    public function __construct(
        private PipelineRepositoryInterface $pipelineRepository,
    ) {
    }

    public function __invoke(GetPipelineQuery $query): PipelineOutput
    {
        $pipeline = $this->pipelineRepository->findById(Uuid::fromString($query->pipelineId));
        if ($pipeline === null) {
            throw NotFoundException::forEntity('Pipeline', $query->pipelineId);
        }

        return PipelineOutput::fromEntity($pipeline);
    }
}
