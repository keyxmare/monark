<?php

declare(strict_types=1);

use App\Catalog\Application\Command\ScanProjectCommand;
use App\Catalog\Application\CommandHandler\ScanProjectHandler;
use App\Catalog\Application\DTO\ScanResultOutput;
use App\Catalog\Domain\Model\DetectedDependency;
use App\Catalog\Domain\Model\DetectedStack;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\ProviderStatus;
use App\Catalog\Domain\Model\ProviderType;
use App\Catalog\Domain\Model\ScanResult;
use App\Catalog\Domain\Model\TechStack;
use App\Catalog\Domain\Port\GitProviderInterface;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Catalog\Domain\Repository\TechStackRepositoryInterface;
use App\Catalog\Infrastructure\GitProvider\GitProviderFactory;
use App\Catalog\Infrastructure\Scanner\ProjectScanner;
use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\DependencyType;
use App\Dependency\Domain\Model\PackageManager;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\ProviderFactory;

function stubScanProjectRepo(?Project $project = null): ProjectRepositoryInterface
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

function stubScanTechStackRepo(): TechStackRepositoryInterface
{
    return new class implements TechStackRepositoryInterface {
        /** @var list<TechStack> */
        public array $saved = [];
        public bool $deletedByProject = false;
        public function findById(Uuid $id): ?TechStack { return null; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array { return []; }
        public function countByProjectId(Uuid $projectId): int { return 0; }
        public function count(): int { return 0; }
        public function save(TechStack $techStack): void { $this->saved[] = $techStack; }
        public function delete(TechStack $techStack): void {}
        public function deleteByProjectId(Uuid $projectId): void { $this->deletedByProject = true; }
    };
}

function stubScanDependencyRepo(): DependencyRepositoryInterface
{
    return new class implements DependencyRepositoryInterface {
        /** @var list<Dependency> */
        public array $saved = [];
        public bool $deletedByProject = false;
        public function findById(Uuid $id): ?Dependency { return null; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array { return []; }
        public function count(): int { return 0; }
        public function countByProjectId(Uuid $projectId): int { return 0; }
        public function save(Dependency $dependency): void { $this->saved[] = $dependency; }
        public function delete(Dependency $dependency): void {}
        public function deleteByProjectId(Uuid $projectId): void { $this->deletedByProject = true; }
    };
}

function stubProjectScanner(ScanResult $result): ProjectScanner
{
    return new class ($result) extends ProjectScanner {
        public function __construct(private readonly ScanResult $scanResult)
        {
        }

        public function scan(Project $project): ScanResult
        {
            return $this->scanResult;
        }
    };
}

describe('ScanProjectHandler', function () {
    it('scans a project and persists tech stacks and dependencies', function () {
        $provider = ProviderFactory::create();
        $project = Project::create(
            name: 'Test Project',
            slug: 'test-project',
            description: null,
            repositoryUrl: 'https://gitlab.example.com/test.git',
            defaultBranch: 'main',
            visibility: ProjectVisibility::Private,
            ownerId: Uuid::v7(),
            provider: $provider,
            externalId: '42',
        );

        $projectRepo = stubScanProjectRepo($project);
        $techStackRepo = stubScanTechStackRepo();
        $depRepo = stubScanDependencyRepo();

        $scanResult = new ScanResult(
            stacks: [
                new DetectedStack(language: 'PHP', framework: 'Symfony', version: '8.4', frameworkVersion: '8.0'),
                new DetectedStack(language: 'TypeScript', framework: 'Vue', version: '', frameworkVersion: '3.5.0'),
            ],
            dependencies: [
                new DetectedDependency(name: 'symfony/framework-bundle', currentVersion: '8.0.0', packageManager: PackageManager::Composer, type: DependencyType::Runtime),
                new DetectedDependency(name: 'vue', currentVersion: '3.5.0', packageManager: PackageManager::Npm, type: DependencyType::Runtime),
            ],
        );

        $scanner = stubProjectScanner($scanResult);
        $handler = new ScanProjectHandler($projectRepo, $techStackRepo, $depRepo, $scanner);

        $result = $handler(new ScanProjectCommand($project->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(ScanResultOutput::class);
        expect($result->stacksDetected)->toBe(2);
        expect($result->dependenciesDetected)->toBe(2);
        expect($result->stacks)->toHaveCount(2);
        expect($result->stacks[0]['language'])->toBe('PHP');
        expect($result->stacks[0]['framework'])->toBe('Symfony');
        expect($result->stacks[0]['version'])->toBe('8.4');
        expect($result->stacks[0]['frameworkVersion'])->toBe('8.0');
        expect($result->dependencies[0]['name'])->toBe('symfony/framework-bundle');
        expect($result->dependencies[1]['packageManager'])->toBe('npm');
        expect($techStackRepo->saved)->toHaveCount(2);
        expect($techStackRepo->deletedByProject)->toBeTrue();
        expect($depRepo->saved)->toHaveCount(2);
        expect($depRepo->deletedByProject)->toBeTrue();
    });

    it('throws not found for unknown project', function () {
        $projectRepo = stubScanProjectRepo(null);
        $techStackRepo = stubScanTechStackRepo();
        $depRepo = stubScanDependencyRepo();
        $scanner = stubProjectScanner(new ScanResult(stacks: [], dependencies: []));

        $handler = new ScanProjectHandler($projectRepo, $techStackRepo, $depRepo, $scanner);

        $handler(new ScanProjectCommand(Uuid::v7()->toRfc4122()));
    })->throws(\DomainException::class);

    it('throws when project has no provider', function () {
        $project = Project::create(
            name: 'No Provider',
            slug: 'no-provider',
            description: null,
            repositoryUrl: 'https://example.com',
            defaultBranch: 'main',
            visibility: ProjectVisibility::Private,
            ownerId: Uuid::v7(),
        );

        $projectRepo = stubScanProjectRepo($project);
        $techStackRepo = stubScanTechStackRepo();
        $depRepo = stubScanDependencyRepo();
        $scanner = stubProjectScanner(new ScanResult(stacks: [], dependencies: []));

        $handler = new ScanProjectHandler($projectRepo, $techStackRepo, $depRepo, $scanner);

        $handler(new ScanProjectCommand($project->getId()->toRfc4122()));
    })->throws(\DomainException::class);
});
