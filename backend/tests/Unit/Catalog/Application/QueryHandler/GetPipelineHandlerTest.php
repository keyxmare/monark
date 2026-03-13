<?php

declare(strict_types=1);

use App\Catalog\Application\DTO\PipelineOutput;
use App\Catalog\Application\Query\GetPipelineQuery;
use App\Catalog\Application\QueryHandler\GetPipelineHandler;
use App\Catalog\Domain\Model\Pipeline;
use App\Catalog\Domain\Repository\PipelineRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\PipelineFactory;

function stubGetPipelineRepo(?Pipeline $pipeline = null): PipelineRepositoryInterface
{
    return new class ($pipeline) implements PipelineRepositoryInterface {
        public function __construct(private readonly ?Pipeline $pipeline)
        {
        }
        public function findById(Uuid $id): ?Pipeline
        {
            return $this->pipeline;
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20, ?string $ref = null): array
        {
            return [];
        }
        public function countByProjectId(Uuid $projectId, ?string $ref = null): int
        {
            return 0;
        }
        public function count(): int
        {
            return 0;
        }
        public function save(Pipeline $pipeline): void
        {
        }
    };
}

describe('GetPipelineHandler', function () {
    it('returns a pipeline by id', function () {
        $pipeline = PipelineFactory::create(externalId: '12345', ref: 'main');
        $handler = new GetPipelineHandler(\stubGetPipelineRepo($pipeline));
        $result = $handler(new GetPipelineQuery($pipeline->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(PipelineOutput::class);
        expect($result->externalId)->toBe('12345');
        expect($result->ref)->toBe('main');
    });

    it('throws not found when pipeline does not exist', function () {
        $handler = new GetPipelineHandler(\stubGetPipelineRepo(null));
        $handler(new GetPipelineQuery('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});
