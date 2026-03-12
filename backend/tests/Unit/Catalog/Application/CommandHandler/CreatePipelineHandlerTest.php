<?php

declare(strict_types=1);

use App\Catalog\Application\Command\CreatePipelineCommand;
use App\Catalog\Application\CommandHandler\CreatePipelineHandler;
use App\Catalog\Application\DTO\CreatePipelineInput;
use App\Catalog\Application\DTO\PipelineOutput;
use App\Catalog\Domain\Model\Pipeline;
use App\Catalog\Domain\Model\PipelineStatus;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Repository\PipelineRepositoryInterface;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\ProjectFactory;

function stubCreatePipelineProjectRepo(?Project $project = null): ProjectRepositoryInterface
{
    return new class ($project) implements ProjectRepositoryInterface {
        public function __construct(private readonly ?Project $project) {}
        public function findById(Uuid $id): ?Project { return $this->project; }
        public function findBySlug(string $slug): ?Project { return null; }
        public function findByExternalIdAndProvider(string $externalId, Uuid $providerId): ?Project { return null; }
        public function findExternalIdMapByProvider(Uuid $providerId): array { return []; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function findByProviderId(Uuid $providerId): array { return []; }
        public function findAllWithProvider(): array { return []; }
        public function count(): int { return 0; }
        public function save(Project $project): void {}
        public function delete(Project $project): void {}
    };
}

function stubCreatePipelineRepo(): PipelineRepositoryInterface
{
    return new class implements PipelineRepositoryInterface {
        public ?Pipeline $saved = null;
        public function findById(Uuid $id): ?Pipeline { return null; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20, ?string $ref = null): array { return []; }
        public function countByProjectId(Uuid $projectId, ?string $ref = null): int { return 0; }
        public function count(): int { return 0; }
        public function save(Pipeline $pipeline): void { $this->saved = $pipeline; }
    };
}

describe('CreatePipelineHandler', function () {
    it('creates a pipeline successfully', function () {
        $project = ProjectFactory::create();
        $pipelineRepo = stubCreatePipelineRepo();
        $projectRepo = stubCreatePipelineProjectRepo($project);
        $handler = new CreatePipelineHandler($pipelineRepo, $projectRepo);

        $input = new CreatePipelineInput(
            externalId: '12345',
            ref: 'main',
            status: PipelineStatus::Success,
            duration: 120,
            startedAt: '2026-03-10T12:00:00+00:00',
            finishedAt: '2026-03-10T12:02:00+00:00',
            projectId: $project->getId()->toRfc4122(),
        );

        $result = $handler(new CreatePipelineCommand($input));

        expect($result)->toBeInstanceOf(PipelineOutput::class);
        expect($result->externalId)->toBe('12345');
        expect($result->status)->toBe('success');
        expect($result->duration)->toBe(120);
        expect($pipelineRepo->saved)->not->toBeNull();
    });

    it('throws not found when project does not exist', function () {
        $handler = new CreatePipelineHandler(
            stubCreatePipelineRepo(),
            stubCreatePipelineProjectRepo(null),
        );

        $input = new CreatePipelineInput(
            externalId: '12345',
            ref: 'main',
            status: PipelineStatus::Success,
            duration: 120,
            startedAt: '2026-03-10T12:00:00+00:00',
            projectId: '00000000-0000-0000-0000-000000000000',
        );
        $handler(new CreatePipelineCommand($input));
    })->throws(NotFoundException::class);
});
