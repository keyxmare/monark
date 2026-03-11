<?php

declare(strict_types=1);

use App\Catalog\Application\DTO\ProjectOutput;
use App\Catalog\Application\Query\GetProjectQuery;
use App\Catalog\Application\QueryHandler\GetProjectHandler;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\ProjectFactory;

function stubGetProjectRepo(?Project $project = null): ProjectRepositoryInterface
{
    return new class ($project) implements ProjectRepositoryInterface {
        public function __construct(private readonly ?Project $project) {}
        public function findById(Uuid $id): ?Project { return $this->project; }
        public function findBySlug(string $slug): ?Project { return null; }
        public function findByExternalIdAndProvider(string $externalId, Uuid $providerId): ?Project { return null; }
        public function findExternalIdsByProvider(Uuid $providerId): array { return []; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function count(): int { return 0; }
        public function save(Project $project): void {}
        public function delete(Project $project): void {}
    };
}

describe('GetProjectHandler', function () {
    it('returns a project by id', function () {
        $project = ProjectFactory::create(name: 'My Project', slug: 'my-project');
        $handler = new GetProjectHandler(stubGetProjectRepo($project));
        $result = $handler(new GetProjectQuery($project->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(ProjectOutput::class);
        expect($result->name)->toBe('My Project');
        expect($result->slug)->toBe('my-project');
    });

    it('throws not found when project does not exist', function () {
        $handler = new GetProjectHandler(stubGetProjectRepo(null));
        $handler(new GetProjectQuery('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});
