<?php

declare(strict_types=1);

use App\Catalog\Application\DTO\TechStackListOutput;
use App\Catalog\Application\Query\ListTechStacksQuery;
use App\Catalog\Application\QueryHandler\ListTechStacksHandler;
use App\Catalog\Domain\Model\TechStack;
use App\Catalog\Domain\Repository\TechStackRepositoryInterface;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\TechStackFactory;

function stubListTechStacksRepo(array $techStacks = [], int $count = 0): TechStackRepositoryInterface
{
    return new class ($techStacks, $count) implements TechStackRepositoryInterface {
        public function __construct(private readonly array $techStacks, private readonly int $count)
        {
        }
        public function findById(Uuid $id): ?TechStack
        {
            return null;
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return $this->techStacks;
        }
        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array
        {
            return $this->techStacks;
        }
        public function countByProjectId(Uuid $projectId): int
        {
            return $this->count;
        }
        public function count(): int
        {
            return $this->count;
        }
        public function save(TechStack $techStack): void
        {
        }
        public function delete(TechStack $techStack): void
        {
        }
        public function deleteByProjectId(Uuid $projectId): void
        {
        }
        public function findByFramework(string $framework): array
        {
            return [];
        }
        public function findByLanguage(string $language): array
        {
            return [];
        }
    };
}

describe('ListTechStacksHandler', function () {
    it('returns paginated tech stacks', function () {
        $ts1 = TechStackFactory::create(language: 'PHP', framework: 'Symfony');
        $ts2 = TechStackFactory::create(language: 'TypeScript', framework: 'Vue.js');

        $handler = new ListTechStacksHandler(\stubListTechStacksRepo([$ts1, $ts2], 2));
        $result = $handler(new ListTechStacksQuery(null, 1, 20));

        expect($result)->toBeInstanceOf(TechStackListOutput::class);
        expect($result->pagination->items)->toHaveCount(2);
        expect($result->pagination->total)->toBe(2);
    });

    it('returns empty list when no tech stacks', function () {
        $handler = new ListTechStacksHandler(\stubListTechStacksRepo([], 0));
        $result = $handler(new ListTechStacksQuery());

        expect($result->pagination->items)->toBeEmpty();
        expect($result->pagination->total)->toBe(0);
    });
});
