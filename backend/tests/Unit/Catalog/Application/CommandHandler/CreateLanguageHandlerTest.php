<?php

declare(strict_types=1);

use App\Catalog\Application\Command\CreateLanguageCommand;
use App\Catalog\Application\CommandHandler\CreateLanguageHandler;
use App\Catalog\Application\DTO\CreateLanguageInput;
use App\Catalog\Application\DTO\LanguageOutput;
use App\Catalog\Domain\Model\Language;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Repository\LanguageRepositoryInterface;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\ProjectFactory;

function stubCreateLanguageProjectRepo(?Project $project = null): ProjectRepositoryInterface
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

function stubCreateLanguageRepo(): LanguageRepositoryInterface
{
    return new class () implements LanguageRepositoryInterface {
        public ?Language $saved = null;

        public function findById(Uuid $id): ?Language
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
        public function findByNameAndProjectId(string $name, Uuid $projectId): ?Language
        {
            return null;
        }
        public function save(Language $language): void
        {
            $this->saved = $language;
        }
        public function delete(Language $language): void
        {
        }
        public function deleteByProjectId(Uuid $projectId): void
        {
        }
    };
}

describe('CreateLanguageHandler', function () {
    it('creates a language and returns output', function () {
        $project = ProjectFactory::create();
        $languageRepo = \stubCreateLanguageRepo();
        $projectRepo = \stubCreateLanguageProjectRepo($project);

        $input = new CreateLanguageInput(
            name: 'PHP',
            version: '8.4',
            detectedAt: '2026-03-30T00:00:00+00:00',
            projectId: $project->getId()->toRfc4122(),
        );

        $handler = new CreateLanguageHandler($languageRepo, $projectRepo);
        $result = $handler(new CreateLanguageCommand($input));

        expect($result)->toBeInstanceOf(LanguageOutput::class)
            ->and($result->name)->toBe('PHP')
            ->and($result->version)->toBe('8.4')
            ->and($languageRepo->saved)->not->toBeNull();
    });

    it('throws NotFoundException when project does not exist', function () {
        $handler = new CreateLanguageHandler(
            \stubCreateLanguageRepo(),
            \stubCreateLanguageProjectRepo(null),
        );

        $input = new CreateLanguageInput(
            name: 'PHP',
            version: '8.4',
            detectedAt: '2026-03-30T00:00:00+00:00',
            projectId: '00000000-0000-0000-0000-000000000000',
        );

        $handler(new CreateLanguageCommand($input));
    })->throws(NotFoundException::class);
});
