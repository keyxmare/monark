<?php

declare(strict_types=1);

use App\Catalog\Application\Command\CreateFrameworkCommand;
use App\Catalog\Application\CommandHandler\CreateFrameworkHandler;
use App\Catalog\Application\DTO\CreateFrameworkInput;
use App\Catalog\Application\DTO\FrameworkOutput;
use App\Catalog\Domain\Model\Framework;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\ProjectFactory;

function stubCreateFrameworkProjectRepo(?Project $project = null): ProjectRepositoryInterface
{
    return new class ($project) implements ProjectRepositoryInterface {
        public function __construct(private readonly ?Project $project)
        {
        }

        public function findById(Uuid $id): ?Project
        {
            return $this->project;
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
            return [];
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
            return 0;
        }
        public function save(Project $project): void
        {
        }
        public function delete(Project $project): void
        {
        }
    };
}

function stubCreateFrameworkRepo(): FrameworkRepositoryInterface
{
    return new class () implements FrameworkRepositoryInterface {
        public ?Framework $saved = null;

        public function findById(Uuid $id): ?Framework
        {
            return null;
        }
        public function findAll(): array
        {
            return [];
        }
        public function findByProjectId(Uuid $projectId): array
        {
            return [];
        }
        public function findByLanguageId(Uuid $languageId): array
        {
            return [];
        }
        public function findByNameAndProjectId(string $name, Uuid $projectId): ?Framework
        {
            return null;
        }
        public function findByName(string $name): array
        {
            return [];
        }
        public function save(Framework $framework): void
        {
            $this->saved = $framework;
        }
        public function delete(Framework $framework): void
        {
        }
        public function deleteByProjectId(Uuid $projectId): void
        {
        }
    };
}

describe('CreateFrameworkHandler', function () {
    it('creates a framework and returns output', function () {
        $project = ProjectFactory::create();
        $frameworkRepo = \stubCreateFrameworkRepo();
        $projectRepo = \stubCreateFrameworkProjectRepo($project);

        $input = new CreateFrameworkInput(
            name: 'Symfony',
            version: '7.1',
            detectedAt: '2026-03-30T00:00:00+00:00',
            languageName: 'PHP',
            languageVersion: '8.4',
            projectId: $project->getId()->toRfc4122(),
        );

        $handler = new CreateFrameworkHandler($frameworkRepo, $projectRepo);
        $result = $handler(new CreateFrameworkCommand($input));

        expect($result)->toBeInstanceOf(FrameworkOutput::class)
            ->and($result->name)->toBe('Symfony')
            ->and($result->version)->toBe('7.1')
            ->and($result->languageName)->toBe('PHP')
            ->and($frameworkRepo->saved)->not->toBeNull();
    });

    it('throws NotFoundException when project does not exist', function () {
        $handler = new CreateFrameworkHandler(
            \stubCreateFrameworkRepo(),
            \stubCreateFrameworkProjectRepo(null),
        );

        $input = new CreateFrameworkInput(
            name: 'Symfony',
            version: '7.1',
            detectedAt: '2026-03-30T00:00:00+00:00',
            languageName: 'PHP',
            languageVersion: '8.4',
            projectId: '00000000-0000-0000-0000-000000000000',
        );

        $handler(new CreateFrameworkCommand($input));
    })->throws(NotFoundException::class);
});
