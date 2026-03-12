<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\RemoteProject;
use App\Catalog\Domain\Port\GitProviderInterface;
use App\Catalog\Infrastructure\GitProvider\GitProviderFactory;
use App\Catalog\Infrastructure\Scanner\ProjectScanner;
use App\Dependency\Domain\Model\DependencyType;
use App\Dependency\Domain\Model\PackageManager;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\ProviderFactory;

function stubScannerGitClient(array $files = [], array $tree = []): GitProviderInterface
{
    return new class ($files, $tree) implements GitProviderInterface {
        public function __construct(
            private readonly array $files,
            private readonly array $tree,
        ) {}

        public function listProjects(Provider $provider, int $page = 1, int $perPage = 20): array { return []; }
        public function countProjects(Provider $provider): int { return 0; }
        public function testConnection(Provider $provider): bool { return true; }
        public function getProject(Provider $provider, string $externalId): RemoteProject { throw new \RuntimeException('Not implemented'); }

        public function getFileContent(Provider $provider, string $externalProjectId, string $filePath, string $ref = 'main'): ?string
        {
            return $this->files[$filePath] ?? null;
        }

        public function listDirectory(Provider $provider, string $externalProjectId, string $path = '', string $ref = 'main'): array
        {
            return $this->tree[$path] ?? [];
        }

        public function listMergeRequests(Provider $provider, string $externalProjectId, ?string $state = null, int $page = 1, int $perPage = 20): array { return []; }
    };
}

function stubScannerFactory(GitProviderInterface $client): GitProviderFactory
{
    return new class ($client) extends GitProviderFactory {
        private GitProviderInterface $client;
        public function __construct(GitProviderInterface $client) { $this->client = $client; }
        public function create(Provider $provider): GitProviderInterface { return $this->client; }
    };
}

function createLinkedProject(?Provider $provider = null): Project
{
    return Project::create(
        name: 'Test',
        slug: 'test',
        description: null,
        repositoryUrl: 'https://gitlab.example.com/test.git',
        defaultBranch: 'main',
        visibility: ProjectVisibility::Private,
        ownerId: Uuid::v7(),
        provider: $provider ?? ProviderFactory::create(),
        externalId: '42',
    );
}

describe('ProjectScanner', function () {
    it('detects PHP + Symfony from composer.json at root', function () {
        $composerJson = \json_encode([
            'require' => [
                'php' => '>=8.4',
                'symfony/framework-bundle' => '^8.0',
                'doctrine/orm' => '^3.0',
            ],
            'require-dev' => [
                'pestphp/pest' => '^4.0',
            ],
        ]);

        $client = stubScannerGitClient(
            files: ['composer.json' => $composerJson],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(stubScannerFactory($client));

        $result = $scanner->scan(createLinkedProject());

        expect($result->stacks)->toHaveCount(1);
        expect($result->stacks[0]->language)->toBe('PHP');
        expect($result->stacks[0]->framework)->toBe('Symfony');
        expect($result->stacks[0]->version)->toBe('8.4');
        expect($result->stacks[0]->frameworkVersion)->toBe('8.0');

        expect($result->dependencies)->toHaveCount(3);
        expect($result->dependencies[0]->name)->toBe('symfony/framework-bundle');
        expect($result->dependencies[0]->packageManager)->toBe(PackageManager::Composer);
        expect($result->dependencies[0]->type)->toBe(DependencyType::Runtime);
        expect($result->dependencies[2]->name)->toBe('pestphp/pest');
        expect($result->dependencies[2]->type)->toBe(DependencyType::Dev);
    });

    it('detects JS/TS + Vue from package.json', function () {
        $packageJson = \json_encode([
            'dependencies' => [
                'vue' => '^3.5.0',
                'pinia' => '^3.0.0',
            ],
            'devDependencies' => [
                'typescript' => '^5.7.0',
                'vite' => '^6.0.0',
            ],
        ]);

        $client = stubScannerGitClient(
            files: ['package.json' => $packageJson],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(stubScannerFactory($client));

        $result = $scanner->scan(createLinkedProject());

        expect($result->stacks)->toHaveCount(1);
        expect($result->stacks[0]->language)->toBe('TypeScript');
        expect($result->stacks[0]->framework)->toBe('Vue');
        expect($result->stacks[0]->version)->toBe('');
        expect($result->stacks[0]->frameworkVersion)->toBe('3.5.0');

        expect($result->dependencies)->toHaveCount(4);
        expect($result->dependencies[0]->packageManager)->toBe(PackageManager::Npm);
    });

    it('scans subdirectories for manifest files', function () {
        $composerJson = \json_encode([
            'require' => ['php' => '>=8.4', 'symfony/framework-bundle' => '^8.0'],
        ]);
        $packageJson = \json_encode([
            'dependencies' => ['vue' => '^3.5.0'],
            'devDependencies' => ['typescript' => '^5.7.0'],
        ]);

        $client = stubScannerGitClient(
            files: [
                'backend/composer.json' => $composerJson,
                'frontend/package.json' => $packageJson,
            ],
            tree: [
                '' => [
                    ['name' => 'backend', 'type' => 'tree', 'path' => 'backend'],
                    ['name' => 'frontend', 'type' => 'tree', 'path' => 'frontend'],
                    ['name' => 'README.md', 'type' => 'blob', 'path' => 'README.md'],
                ],
                'backend' => [
                    ['name' => 'composer.json', 'type' => 'blob', 'path' => 'backend/composer.json'],
                    ['name' => 'src', 'type' => 'tree', 'path' => 'backend/src'],
                ],
                'frontend' => [
                    ['name' => 'package.json', 'type' => 'blob', 'path' => 'frontend/package.json'],
                    ['name' => 'src', 'type' => 'tree', 'path' => 'frontend/src'],
                ],
            ],
        );

        $scanner = new ProjectScanner(stubScannerFactory($client));
        $result = $scanner->scan(createLinkedProject());

        expect($result->stacks)->toHaveCount(2);

        $languages = \array_map(fn ($s) => $s->language, $result->stacks);
        expect($languages)->toContain('PHP');
        expect($languages)->toContain('TypeScript');

        expect(\count($result->dependencies))->toBeGreaterThanOrEqual(3);
    });

    it('enriches versions from composer.lock', function () {
        $composerJson = \json_encode([
            'require' => ['symfony/framework-bundle' => '^8.0'],
        ]);
        $composerLock = \json_encode([
            'packages' => [
                ['name' => 'symfony/framework-bundle', 'version' => 'v8.0.3'],
            ],
            'packages-dev' => [],
        ]);

        $client = stubScannerGitClient(
            files: [
                'composer.json' => $composerJson,
                'composer.lock' => $composerLock,
            ],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(stubScannerFactory($client));

        $result = $scanner->scan(createLinkedProject());

        expect($result->dependencies[0]->currentVersion)->toBe('8.0.3');
    });

    it('returns empty result for project without provider', function () {
        $project = Project::create(
            name: 'No Provider',
            slug: 'no-provider',
            description: null,
            repositoryUrl: 'https://example.com',
            defaultBranch: 'main',
            visibility: ProjectVisibility::Private,
            ownerId: Uuid::v7(),
        );

        $client = stubScannerGitClient();
        $scanner = new ProjectScanner(stubScannerFactory($client));

        $result = $scanner->scan($project);

        expect($result->stacks)->toBeEmpty();
        expect($result->dependencies)->toBeEmpty();
    });

    it('extracts pip dependencies from requirements.txt', function () {
        $requirements = "django==4.2.0\ncelery>=5.3.0\n# comment\nredis\n";

        $client = stubScannerGitClient(
            files: ['requirements.txt' => $requirements],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(stubScannerFactory($client));

        $result = $scanner->scan(createLinkedProject());

        expect($result->stacks)->toHaveCount(1);
        expect($result->stacks[0]->language)->toBe('Python');
        expect($result->stacks[0]->framework)->toBe('Django');

        expect($result->dependencies)->toHaveCount(3);
        expect($result->dependencies[0]->name)->toBe('django');
        expect($result->dependencies[0]->currentVersion)->toBe('4.2.0');
        expect($result->dependencies[0]->packageManager)->toBe(PackageManager::Pip);
    });

    it('deduplicates dependencies across locations', function () {
        $packageJson = \json_encode([
            'dependencies' => ['vue' => '^3.5.0'],
        ]);

        $client = stubScannerGitClient(
            files: [
                'package.json' => $packageJson,
                'frontend/package.json' => $packageJson,
            ],
            tree: [
                '' => [
                    ['name' => 'frontend', 'type' => 'tree', 'path' => 'frontend'],
                ],
                'frontend' => [
                    ['name' => 'package.json', 'type' => 'blob', 'path' => 'frontend/package.json'],
                ],
            ],
        );

        $scanner = new ProjectScanner(stubScannerFactory($client));
        $result = $scanner->scan(createLinkedProject());

        $vueCount = \count(\array_filter($result->dependencies, fn ($d) => $d->name === 'vue'));
        expect($vueCount)->toBe(1);
    });
});
