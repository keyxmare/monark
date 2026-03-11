<?php

declare(strict_types=1);

use App\Catalog\Application\Command\DeleteProjectCommand;
use App\Catalog\Application\CommandHandler\DeleteProjectHandler;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\ProjectFactory;

function stubDeleteProjectRepo(?Project $project = null): ProjectRepositoryInterface
{
    return new class ($project) implements ProjectRepositoryInterface {
        public bool $deleted = false;
        public function __construct(private readonly ?Project $project) {}
        public function findById(Uuid $id): ?Project { return $this->project; }
        public function findBySlug(string $slug): ?Project { return null; }
        public function findByExternalIdAndProvider(string $externalId, Uuid $providerId): ?Project { return null; }
        public function findExternalIdsByProvider(Uuid $providerId): array { return []; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function count(): int { return 0; }
        public function save(Project $project): void {}
        public function delete(Project $project): void { $this->deleted = true; }
    };
}

describe('DeleteProjectHandler', function () {
    it('deletes a project successfully', function () {
        $project = ProjectFactory::create();
        $repo = stubDeleteProjectRepo($project);
        $handler = new DeleteProjectHandler($repo);

        $handler(new DeleteProjectCommand($project->getId()->toRfc4122()));

        expect($repo->deleted)->toBeTrue();
    });

    it('throws not found when project does not exist', function () {
        $handler = new DeleteProjectHandler(stubDeleteProjectRepo(null));
        $handler(new DeleteProjectCommand('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});
