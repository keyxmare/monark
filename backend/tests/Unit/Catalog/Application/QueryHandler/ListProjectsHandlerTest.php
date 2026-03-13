<?php

declare(strict_types=1);

use App\Catalog\Application\DTO\ProjectListOutput;
use App\Catalog\Application\Query\ListProjectsQuery;
use App\Catalog\Application\QueryHandler\ListProjectsHandler;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\ProjectFactory;

function stubListProjectsRepo(array $projects = [], int $count = 0): ProjectRepositoryInterface
{
    return new class ($projects, $count) implements ProjectRepositoryInterface {
        public function __construct(private readonly array $projects, private readonly int $count)
        {
        }
        public function findById(Uuid $id): ?Project
        {
            return null;
        }
        public function findBySlug(string $slug): ?Project
        {
            return null;
        }
        public function findByExternalIdAndProvider(string $externalId, Uuid $providerId): ?Project
        {
            return null;
        }
        public function findExternalIdMapByProvider(Uuid $providerId): array
        {
            return [];
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return $this->projects;
        }
        public function findByProviderId(Uuid $providerId): array
        {
            return [];
        }
        public function findAllWithProvider(): array
        {
            return [];
        }
        public function count(): int
        {
            return $this->count;
        }
        public function save(Project $project): void
        {
        }
        public function delete(Project $project): void
        {
        }
    };
}

describe('ListProjectsHandler', function () {
    it('returns paginated projects', function () {
        $project1 = ProjectFactory::create(name: 'Project 1', slug: 'project-1');
        $project2 = ProjectFactory::create(name: 'Project 2', slug: 'project-2');

        $handler = new ListProjectsHandler(\stubListProjectsRepo([$project1, $project2], 2));
        $result = $handler(new ListProjectsQuery(1, 20));

        expect($result)->toBeInstanceOf(ProjectListOutput::class);
        expect($result->pagination->items)->toHaveCount(2);
        expect($result->pagination->total)->toBe(2);
    });

    it('returns empty list when no projects', function () {
        $handler = new ListProjectsHandler(\stubListProjectsRepo([], 0));
        $result = $handler(new ListProjectsQuery());

        expect($result->pagination->items)->toBeEmpty();
        expect($result->pagination->total)->toBe(0);
    });
});
