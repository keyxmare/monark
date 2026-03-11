<?php

declare(strict_types=1);

use App\Catalog\Application\DTO\PipelineListOutput;
use App\Catalog\Application\Query\ListPipelinesQuery;
use App\Catalog\Application\QueryHandler\ListPipelinesHandler;
use App\Catalog\Domain\Model\Pipeline;
use App\Catalog\Domain\Repository\PipelineRepositoryInterface;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\PipelineFactory;

function stubListPipelinesRepo(array $pipelines = [], int $count = 0): PipelineRepositoryInterface
{
    return new class ($pipelines, $count) implements PipelineRepositoryInterface {
        public ?string $lastRef = null;
        public function __construct(private readonly array $pipelines, private readonly int $count) {}
        public function findById(Uuid $id): ?Pipeline { return null; }
        public function findAll(int $page = 1, int $perPage = 20): array { return $this->pipelines; }
        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20, ?string $ref = null): array {
            $this->lastRef = $ref;
            return $this->pipelines;
        }
        public function countByProjectId(Uuid $projectId, ?string $ref = null): int { return $this->count; }
        public function count(): int { return $this->count; }
        public function save(Pipeline $pipeline): void {}
    };
}

describe('ListPipelinesHandler', function () {
    it('returns paginated pipelines', function () {
        $p1 = PipelineFactory::create(externalId: '111');
        $p2 = PipelineFactory::create(externalId: '222');

        $handler = new ListPipelinesHandler(stubListPipelinesRepo([$p1, $p2], 2));
        $result = $handler(new ListPipelinesQuery(null, 1, 20));

        expect($result)->toBeInstanceOf(PipelineListOutput::class);
        expect($result->pagination->items)->toHaveCount(2);
        expect($result->pagination->total)->toBe(2);
    });

    it('returns empty list when no pipelines', function () {
        $handler = new ListPipelinesHandler(stubListPipelinesRepo([], 0));
        $result = $handler(new ListPipelinesQuery());

        expect($result->pagination->items)->toBeEmpty();
        expect($result->pagination->total)->toBe(0);
    });

    it('passes ref filter to repository', function () {
        $repo = stubListPipelinesRepo([], 0);
        $handler = new ListPipelinesHandler($repo);
        $projectId = Uuid::v7()->toRfc4122();

        $handler(new ListPipelinesQuery($projectId, 1, 10, 'main'));

        expect($repo->lastRef)->toBe('main');
    });
});
