<?php

declare(strict_types=1);

use App\Catalog\Application\Command\ScanProjectCommand;
use App\Catalog\Application\CommandHandler\ScanProjectHandler;
use App\Catalog\Application\DTO\ScanResultOutput;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Model\TechStack;
use App\Catalog\Domain\Port\ProjectScannerInterface;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Catalog\Domain\Repository\TechStackRepositoryInterface;
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

function stubScanTechStackRepo(): TechStackRepositoryInterface
{
    return new class () implements TechStackRepositoryInterface {
        /** @var list<TechStack> */
        public array $saved = [];
        public bool $deletedByProject = false;
        public function findById(Uuid $id): ?TechStack
        {
            return null;
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function countByProjectId(Uuid $projectId): int
        {
            return 0;
        }
        public function count(): int
        {
            return 0;
        }
        public function save(TechStack $techStack): void
        {
            $this->saved[] = $techStack;
        }
        public function delete(TechStack $techStack): void
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
        public function deleteByProjectId(Uuid $projectId): void
        {
            $this->deletedByProject = true;
        }
        public function createFromScan(string $name, string $currentVersion, string $packageManager, string $type, Uuid $projectId, ?string $repositoryUrl): void
        {
            $this->created[] = \compact('name', 'currentVersion', 'packageManager', 'type', 'projectId', 'repositoryUrl');
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

        $projectRepo = \stubScanProjectRepo($project);
        $techStackRepo = \stubScanTechStackRepo();
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
        $handler = new ScanProjectHandler($projectRepo, $techStackRepo, $depWriter, $scanner, $eventBus);

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
        expect($depWriter->created)->toHaveCount(2);
        expect($depWriter->deletedByProject)->toBeTrue();
        expect($eventBus->dispatched)->toHaveCount(1);
        expect($eventBus->dispatched[0])->toBeInstanceOf(ProjectScannedEvent::class);
        expect($eventBus->dispatched[0]->projectId)->toBe($project->getId()->toRfc4122());
        expect($eventBus->dispatched[0]->scanResult->stacks)->toHaveCount(2);
        expect($eventBus->dispatched[0]->scanResult->dependencies)->toHaveCount(2);
    });

    it('throws not found for unknown project', function () {
        $projectRepo = \stubScanProjectRepo(null);
        $techStackRepo = \stubScanTechStackRepo();
        $depWriter = \stubScanDependencyWriter();
        $scanner = \stubProjectScanner(new ScanResult(stacks: [], dependencies: []));

        $eventBus = \spyScanEventBus();
        $handler = new ScanProjectHandler($projectRepo, $techStackRepo, $depWriter, $scanner, $eventBus);

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
        $techStackRepo = \stubScanTechStackRepo();
        $depWriter = \stubScanDependencyWriter();
        $scanner = \stubProjectScanner(new ScanResult(stacks: [], dependencies: []));
        $eventBus = \spyScanEventBus();

        $handler = new ScanProjectHandler($projectRepo, $techStackRepo, $depWriter, $scanner, $eventBus);
        $result = $handler(new ScanProjectCommand($project->getId()->toRfc4122()));

        expect($result->stacksDetected)->toBe(0);
        expect($result->dependenciesDetected)->toBe(0);
        expect($techStackRepo->deletedByProject)->toBeFalse();
        expect($depWriter->deletedByProject)->toBeFalse();
        expect($eventBus->dispatched)->toBeEmpty();
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
        $techStackRepo = \stubScanTechStackRepo();
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
        $handler = new ScanProjectHandler($projectRepo, $techStackRepo, $depWriter, $scanner, $eventBus);

        $result = $handler(new ScanProjectCommand($project->getId()->toRfc4122()));

        expect($result->stacksDetected)->toBe(2);
        expect($techStackRepo->saved)->toHaveCount(2);
        $savedFrameworks = \array_map(fn ($ts) => $ts->getFramework(), $techStackRepo->saved);
        expect($savedFrameworks)->not->toContain('none');
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
        $techStackRepo = \stubScanTechStackRepo();
        $depWriter = \stubScanDependencyWriter();
        $scanner = \stubProjectScanner(new ScanResult(stacks: [], dependencies: []));

        $eventBus = \spyScanEventBus();
        $handler = new ScanProjectHandler($projectRepo, $techStackRepo, $depWriter, $scanner, $eventBus);

        $handler(new ScanProjectCommand($project->getId()->toRfc4122()));
    })->throws(\DomainException::class);
});
