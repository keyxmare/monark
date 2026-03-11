<?php

declare(strict_types=1);

use App\Catalog\Application\Command\CreateTechStackCommand;
use App\Catalog\Application\CommandHandler\CreateTechStackHandler;
use App\Catalog\Application\DTO\CreateTechStackInput;
use App\Catalog\Application\DTO\TechStackOutput;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\TechStack;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Catalog\Domain\Repository\TechStackRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\ProjectFactory;

function stubCreateTechStackProjectRepo(?Project $project = null): ProjectRepositoryInterface
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

function stubCreateTechStackRepo(): TechStackRepositoryInterface
{
    return new class implements TechStackRepositoryInterface {
        public ?TechStack $saved = null;
        public function findById(Uuid $id): ?TechStack { return null; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array { return []; }
        public function countByProjectId(Uuid $projectId): int { return 0; }
        public function count(): int { return 0; }
        public function save(TechStack $techStack): void { $this->saved = $techStack; }
        public function delete(TechStack $techStack): void {}
        public function deleteByProjectId(Uuid $projectId): void {}
    };
}

describe('CreateTechStackHandler', function () {
    it('creates a tech stack successfully', function () {
        $project = ProjectFactory::create();
        $tsRepo = stubCreateTechStackRepo();
        $projectRepo = stubCreateTechStackProjectRepo($project);
        $handler = new CreateTechStackHandler($tsRepo, $projectRepo);

        $input = new CreateTechStackInput(
            language: 'PHP',
            framework: 'Symfony',
            version: '8.0',
            detectedAt: '2026-03-10T12:00:00+00:00',
            projectId: $project->getId()->toRfc4122(),
        );

        $result = $handler(new CreateTechStackCommand($input));

        expect($result)->toBeInstanceOf(TechStackOutput::class);
        expect($result->language)->toBe('PHP');
        expect($result->framework)->toBe('Symfony');
        expect($result->version)->toBe('8.0');
        expect($tsRepo->saved)->not->toBeNull();
    });

    it('throws not found when project does not exist', function () {
        $handler = new CreateTechStackHandler(
            stubCreateTechStackRepo(),
            stubCreateTechStackProjectRepo(null),
        );

        $input = new CreateTechStackInput(
            language: 'PHP',
            framework: 'Symfony',
            version: '8.0',
            detectedAt: '2026-03-10T12:00:00+00:00',
            projectId: '00000000-0000-0000-0000-000000000000',
        );
        $handler(new CreateTechStackCommand($input));
    })->throws(NotFoundException::class);
});
