<?php

declare(strict_types=1);

use App\Catalog\Application\Command\ScanProjectCommand;
use App\Catalog\Application\CommandHandler\ScanProjectHandler;
use App\Catalog\Application\DTO\ScanResultOutput;
use App\Catalog\Domain\Model\Framework;
use App\Catalog\Domain\Model\Language;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Port\ProjectScannerInterface;
use App\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use App\Catalog\Domain\Repository\LanguageRepositoryInterface;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\DTO\DetectedDependency;
use App\Shared\Domain\DTO\DetectedStack;
use App\Shared\Domain\DTO\ScanResult;
use App\Shared\Domain\Event\ProjectScannedEvent;
use App\Shared\Domain\Port\DependencyWriterPort;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\ProviderFactory;

function stubScanGitProviderFactory(): \App\Catalog\Domain\Port\GitProviderFactoryInterface
{
    return new class () implements \App\Catalog\Domain\Port\GitProviderFactoryInterface {
        public function create(\App\Catalog\Domain\Model\Provider $provider): \App\Catalog\Domain\Port\GitProviderInterface
        {
            return new class () implements \App\Catalog\Domain\Port\GitProviderInterface {
                public function listProjects(\App\Catalog\Domain\Model\Provider $provider, int $page = 1, int $perPage = 20, ?string $search = null, ?string $visibility = null, string $sort = 'name', string $sortDir = 'asc'): array
                {
                    return [];
                }
                public function countProjects(\App\Catalog\Domain\Model\Provider $provider, ?string $search = null, ?string $visibility = null): int
                {
                    return 0;
                }
                public function testConnection(\App\Catalog\Domain\Model\Provider $provider): bool
                {
                    return true;
                }
                public function getProject(\App\Catalog\Domain\Model\Provider $provider, string $externalId): \App\Catalog\Domain\Model\RemoteProject
                {
                    throw new \RuntimeException('not implemented');
                }
                public function getFileContent(\App\Catalog\Domain\Model\Provider $provider, string $externalProjectId, string $filePath, string $ref = 'main'): ?string
                {
                    return null;
                }
                public function listDirectory(\App\Catalog\Domain\Model\Provider $provider, string $externalProjectId, string $path = '', string $ref = 'main'): array
                {
                    return [];
                }
                public function listBranches(\App\Catalog\Domain\Model\Provider $provider, string $externalProjectId): array
                {
                    return ['main'];
                }
            };
        }
    };
}

function spyScanEventBus(): object
{
    return new class () implements MessageBusInterface {
        /** @var list<object> */
        public array $dispatched = [];
        public function dispatch(object $message, array $stamps = []): Envelope
        {
            $this->dispatched[] = $message;
            return Envelope::wrap($message, $stamps);
        }
    };
}

function stubScanProjectRepo(?Project $project = null): ProjectRepositoryInterface
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

function stubScanLanguageRepo(): LanguageRepositoryInterface
{
    return new class () implements LanguageRepositoryInterface {
        /** @var list<Language> */
        public array $saved = [];
        public bool $deletedByProject = false;

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
            $this->saved[] = $language;
        }
        public function delete(Language $language): void
        {
        }
        public function deleteByProjectId(Uuid $projectId): void
        {
            $this->deletedByProject = true;
        }
    };
}

function stubScanFrameworkRepo(): FrameworkRepositoryInterface
{
    return new class () implements FrameworkRepositoryInterface {
        /** @var list<Framework> */
        public array $saved = [];
        public bool $deletedByProject = false;

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
            $this->saved[] = $framework;
        }
        public function delete(Framework $framework): void
        {
        }
        public function deleteByProjectId(Uuid $projectId): void
        {
            $this->deletedByProject = true;
        }
    };
}

function stubScanDependencyWriter(): DependencyWriterPort
{
    return new class () implements DependencyWriterPort {
        /** @var list<array{name: string, currentVersion: string, packageManager: string, type: string, projectId: Uuid, repositoryUrl: ?string}> */
        public array $created = [];
        public bool $deletedByProject = false;
        public bool $removedStale = false;
        public function deleteByProjectId(Uuid $projectId): void
        {
            $this->deletedByProject = true;
        }
        public function upsertFromScan(string $name, string $currentVersion, string $packageManager, string $type, Uuid $projectId, ?string $repositoryUrl): void
        {
            $this->created[] = \compact('name', 'currentVersion', 'packageManager', 'type', 'projectId', 'repositoryUrl');
        }
        public function removeStaleByProjectId(Uuid $projectId, array $scannedDeps): void
        {
            $this->removedStale = true;
        }
    };
}

function stubProjectScanner(ScanResult $result): ProjectScannerInterface
{
    return new class ($result) implements ProjectScannerInterface {
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
    it('scans a project and persists languages, frameworks and dependencies', function () {
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

        $projectRepo = \stubScanProjectRepo($project);
        $languageRepo = \stubScanLanguageRepo();
        $frameworkRepo = \stubScanFrameworkRepo();
        $depWriter = \stubScanDependencyWriter();

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

        $scanner = \stubProjectScanner($scanResult);
        $eventBus = \spyScanEventBus();
        $handler = new ScanProjectHandler($projectRepo, $languageRepo, $frameworkRepo, $depWriter, \stubScanGitProviderFactory(), $scanner, $eventBus);

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
        expect($languageRepo->saved)->toHaveCount(2);
        expect($languageRepo->deletedByProject)->toBeTrue();
        expect($frameworkRepo->saved)->toHaveCount(2);
        expect($frameworkRepo->deletedByProject)->toBeTrue();
        expect($depWriter->created)->toHaveCount(2);
        expect($depWriter->removedStale)->toBeTrue();
        expect($eventBus->dispatched)->toHaveCount(1);
        expect($eventBus->dispatched[0])->toBeInstanceOf(ProjectScannedEvent::class);
        expect($eventBus->dispatched[0]->projectId)->toBe($project->getId()->toRfc4122());
        expect($eventBus->dispatched[0]->scanResult->stacks)->toHaveCount(2);
        expect($eventBus->dispatched[0]->scanResult->dependencies)->toHaveCount(2);
    });

    it('throws not found for unknown project', function () {
        $projectRepo = \stubScanProjectRepo(null);
        $languageRepo = \stubScanLanguageRepo();
        $frameworkRepo = \stubScanFrameworkRepo();
        $depWriter = \stubScanDependencyWriter();
        $scanner = \stubProjectScanner(new ScanResult(stacks: [], dependencies: []));

        $eventBus = \spyScanEventBus();
        $handler = new ScanProjectHandler($projectRepo, $languageRepo, $frameworkRepo, $depWriter, \stubScanGitProviderFactory(), $scanner, $eventBus);

        $handler(new ScanProjectCommand(Uuid::v7()->toRfc4122()));
    })->throws(\DomainException::class);

    it('preserves existing data when scan returns empty result', function () {
        $provider = ProviderFactory::create();
        $project = Project::create(
            name: 'Test',
            slug: 'test',
            description: null,
            repositoryUrl: 'https://gitlab.example.com/test.git',
            defaultBranch: 'main',
            visibility: ProjectVisibility::Private,
            ownerId: Uuid::v7(),
            provider: $provider,
            externalId: '42',
        );

        $projectRepo = \stubScanProjectRepo($project);
        $languageRepo = \stubScanLanguageRepo();
        $frameworkRepo = \stubScanFrameworkRepo();
        $depWriter = \stubScanDependencyWriter();
        $scanner = \stubProjectScanner(new ScanResult(stacks: [], dependencies: []));
        $eventBus = \spyScanEventBus();

        $handler = new ScanProjectHandler($projectRepo, $languageRepo, $frameworkRepo, $depWriter, \stubScanGitProviderFactory(), $scanner, $eventBus);
        $result = $handler(new ScanProjectCommand($project->getId()->toRfc4122()));

        expect($result->stacksDetected)->toBe(0);
        expect($result->dependenciesDetected)->toBe(0);
        expect($languageRepo->deletedByProject)->toBeFalse();
        expect($frameworkRepo->deletedByProject)->toBeFalse();
        expect($depWriter->deletedByProject)->toBeFalse();
        expect($eventBus->dispatched)->toHaveCount(1);
        expect($eventBus->dispatched[0])->toBeInstanceOf(ProjectScannedEvent::class);
    });

    it('skips stacks without framework', function () {
        $provider = ProviderFactory::create();
        $project = Project::create(
            name: 'Test',
            slug: 'test',
            description: null,
            repositoryUrl: 'https://gitlab.example.com/test.git',
            defaultBranch: 'main',
            visibility: ProjectVisibility::Private,
            ownerId: Uuid::v7(),
            provider: $provider,
            externalId: '42',
        );

        $projectRepo = \stubScanProjectRepo($project);
        $languageRepo = \stubScanLanguageRepo();
        $frameworkRepo = \stubScanFrameworkRepo();
        $depWriter = \stubScanDependencyWriter();

        $scanResult = new ScanResult(
            stacks: [
                new DetectedStack(language: 'PHP', framework: 'Symfony', version: '8.4', frameworkVersion: '8.0'),
                new DetectedStack(language: 'JavaScript', framework: 'none', version: '', frameworkVersion: ''),
                new DetectedStack(language: 'TypeScript', framework: 'Vue', version: '', frameworkVersion: '3.5.0'),
            ],
            dependencies: [],
        );

        $scanner = \stubProjectScanner($scanResult);
        $eventBus = \spyScanEventBus();
        $handler = new ScanProjectHandler($projectRepo, $languageRepo, $frameworkRepo, $depWriter, \stubScanGitProviderFactory(), $scanner, $eventBus);

        $result = $handler(new ScanProjectCommand($project->getId()->toRfc4122()));

        expect($result->stacksDetected)->toBe(2);
        expect($frameworkRepo->saved)->toHaveCount(2);
        $savedFrameworkNames = \array_map(fn ($fw) => $fw->getName(), $frameworkRepo->saved);
        expect($savedFrameworkNames)->not->toContain('none');
    });

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

        $projectRepo = \stubScanProjectRepo($project);
        $languageRepo = \stubScanLanguageRepo();
        $frameworkRepo = \stubScanFrameworkRepo();
        $depWriter = \stubScanDependencyWriter();
        $scanner = \stubProjectScanner(new ScanResult(stacks: [], dependencies: []));

        $eventBus = \spyScanEventBus();
        $handler = new ScanProjectHandler($projectRepo, $languageRepo, $frameworkRepo, $depWriter, \stubScanGitProviderFactory(), $scanner, $eventBus);

        $handler(new ScanProjectCommand($project->getId()->toRfc4122()));
    })->throws(\DomainException::class);
});
