<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\RemoteProject;
use App\Catalog\Domain\Port\GitProviderFactoryInterface;
use App\Catalog\Domain\Port\GitProviderInterface;
use App\Catalog\Infrastructure\Scanner\Detector\DockerDetector;
use App\Catalog\Infrastructure\Scanner\Detector\GoDetector;
use App\Catalog\Infrastructure\Scanner\Detector\JavaScriptDetector;
use App\Catalog\Infrastructure\Scanner\Detector\PhpDetector;
use App\Catalog\Infrastructure\Scanner\Detector\PythonDetector;
use App\Catalog\Infrastructure\Scanner\Detector\RubyDetector;
use App\Catalog\Infrastructure\Scanner\Detector\RustDetector;
use App\Catalog\Infrastructure\Scanner\ProjectScanner;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\ProviderFactory;

function allDetectors(): array
{
    return [
        new PhpDetector(),
        new JavaScriptDetector(),
        new PythonDetector(),
        new GoDetector(),
        new RustDetector(),
        new RubyDetector(),
        new DockerDetector(),
    ];
}

function stubScannerGitClient(array $files = [], array $tree = []): GitProviderInterface
{
    return new class ($files, $tree) implements GitProviderInterface {
        public function __construct(
            private readonly array $files,
            private readonly array $tree,
        ) {
        }

        public function listProjects(Provider $provider, int $page = 1, int $perPage = 20, ?string $search = null, ?string $visibility = null, string $sort = 'name', string $sortDir = 'asc'): array
        {
            return [];
        }
        public function countProjects(Provider $provider, ?string $search = null, ?string $visibility = null): int
        {
            return 0;
        }
        public function testConnection(Provider $provider): bool
        {
            return true;
        }
        public function getProject(Provider $provider, string $externalId): RemoteProject
        {
            throw new \RuntimeException('Not implemented');
        }

        public function getFileContent(Provider $provider, string $externalProjectId, string $filePath, string $ref = 'main'): ?string
        {
            return $this->files[$filePath] ?? null;
        }

        public function listDirectory(Provider $provider, string $externalProjectId, string $path = '', string $ref = 'main'): array
        {
            return $this->tree[$path] ?? [];
        }

        public function listBranches(Provider $provider, string $externalProjectId): array
        {
            return [];
        }

        public function listCommits(Provider $provider, string $externalProjectId, string $ref, ?\DateTimeImmutable $since = null, ?\DateTimeImmutable $until = null, int $perPage = 100): array
        {
            return [];
        }

    };
}

function stubScannerFactory(GitProviderInterface $client): GitProviderFactoryInterface
{
    return new class ($client) implements GitProviderFactoryInterface {
        private GitProviderInterface $client;
        public function __construct(GitProviderInterface $client)
        {
            $this->client = $client;
        }
        public function create(Provider $provider): GitProviderInterface
        {
            return $this->client;
        }
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

        $client = \stubScannerGitClient(
            files: ['composer.json' => $composerJson],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks)->toHaveCount(1);
        expect($result->stacks[0]->language)->toBe('PHP');
        expect($result->stacks[0]->framework)->toBe('Symfony');
        expect($result->stacks[0]->version)->toBe('8.4');
        expect($result->stacks[0]->frameworkVersion)->toBe('8.0');

        expect($result->dependencies)->toHaveCount(3);
        expect($result->dependencies[0]->name)->toBe('symfony/framework-bundle');
        expect($result->dependencies[0]->currentVersion)->toBe('8.0');
        expect($result->dependencies[0]->packageManager)->toBe(PackageManager::Composer);
        expect($result->dependencies[0]->type)->toBe(DependencyType::Runtime);
        expect($result->dependencies[1]->name)->toBe('doctrine/orm');
        expect($result->dependencies[1]->currentVersion)->toBe('3.0');
        expect($result->dependencies[1]->type)->toBe(DependencyType::Runtime);
        expect($result->dependencies[2]->name)->toBe('pestphp/pest');
        expect($result->dependencies[2]->currentVersion)->toBe('4.0');
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

        $client = \stubScannerGitClient(
            files: ['package.json' => $packageJson],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks)->toHaveCount(1);
        expect($result->stacks[0]->language)->toBe('TypeScript');
        expect($result->stacks[0]->framework)->toBe('Vue');
        expect($result->stacks[0]->version)->toBe('');
        expect($result->stacks[0]->frameworkVersion)->toBe('3.5.0');

        expect($result->dependencies)->toHaveCount(4);
        expect($result->dependencies[0]->name)->toBe('vue');
        expect($result->dependencies[0]->currentVersion)->toBe('3.5.0');
        expect($result->dependencies[0]->packageManager)->toBe(PackageManager::Npm);
        expect($result->dependencies[0]->type)->toBe(DependencyType::Runtime);
        expect($result->dependencies[2]->name)->toBe('typescript');
        expect($result->dependencies[2]->type)->toBe(DependencyType::Dev);
    });

    it('scans subdirectories for manifest files', function () {
        $composerJson = \json_encode([
            'require' => ['php' => '>=8.4', 'symfony/framework-bundle' => '^8.0'],
        ]);
        $packageJson = \json_encode([
            'dependencies' => ['vue' => '^3.5.0'],
            'devDependencies' => ['typescript' => '^5.7.0'],
        ]);

        $client = \stubScannerGitClient(
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

        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());
        $result = $scanner->scan(\createLinkedProject());

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

        $client = \stubScannerGitClient(
            files: [
                'composer.json' => $composerJson,
                'composer.lock' => $composerLock,
            ],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

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

        $client = \stubScannerGitClient();
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan($project);

        expect($result->stacks)->toBeEmpty();
        expect($result->dependencies)->toBeEmpty();
    });

    it('extracts pip dependencies from requirements.txt', function () {
        $requirements = "django==4.2.0\ncelery>=5.3.0\n# comment\nredis\n";

        $client = \stubScannerGitClient(
            files: ['requirements.txt' => $requirements],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks)->toHaveCount(1);
        expect($result->stacks[0]->language)->toBe('Python');
        expect($result->stacks[0]->framework)->toBe('Django');

        expect($result->dependencies)->toHaveCount(3);
        expect($result->dependencies[0]->name)->toBe('django');
        expect($result->dependencies[0]->currentVersion)->toBe('4.2.0');
        expect($result->dependencies[0]->packageManager)->toBe(PackageManager::Pip);
        expect($result->dependencies[0]->type)->toBe(DependencyType::Runtime);
        expect($result->dependencies[1]->name)->toBe('celery');
        expect($result->dependencies[1]->currentVersion)->toBe('5.3.0');
        expect($result->dependencies[2]->name)->toBe('redis');
        expect($result->dependencies[2]->currentVersion)->toBe('*');
    });

    it('detects Go + Gin from go.mod', function () {
        $goMod = "module github.com/example/app\n\ngo 1.22\n\nrequire (\n\tgithub.com/gin-gonic/gin v1.10.0\n)";

        $client = \stubScannerGitClient(
            files: ['go.mod' => $goMod],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks)->toHaveCount(1);
        expect($result->stacks[0]->language)->toBe('Go');
        expect($result->stacks[0]->framework)->toBe('Gin');
        expect($result->stacks[0]->version)->toBe('1.22');
        expect($result->stacks[0]->frameworkVersion)->toBe('');
    });

    it('detects Rust + Actix from Cargo.toml', function () {
        $cargoToml = "[package]\nname = \"my-app\"\nversion = \"0.5.0\"\n\n[dependencies]\nactix-web = \"4\"";

        $client = \stubScannerGitClient(
            files: ['Cargo.toml' => $cargoToml],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks)->toHaveCount(1);
        expect($result->stacks[0]->language)->toBe('Rust');
        expect($result->stacks[0]->framework)->toBe('Actix');
        expect($result->stacks[0]->version)->toBe('0.5.0');
        expect($result->stacks[0]->frameworkVersion)->toBe('');
    });

    it('detects Ruby + Rails from Gemfile', function () {
        $gemfile = "source 'https://rubygems.org'\nruby '3.3.0'\ngem 'rails', '~> 7.1'";

        $client = \stubScannerGitClient(
            files: ['Gemfile' => $gemfile],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks)->toHaveCount(1);
        expect($result->stacks[0]->language)->toBe('Ruby');
        expect($result->stacks[0]->framework)->toBe('Rails');
        expect($result->stacks[0]->version)->toBe('3.3.0');
    });

    it('detects language from Dockerfile base image', function () {
        $dockerfile = "FROM php:8.4-fpm\nRUN apt-get update";

        $client = \stubScannerGitClient(
            files: ['Dockerfile' => $dockerfile],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks)->toHaveCount(1);
        expect($result->stacks[0]->language)->toBe('PHP');
        expect($result->stacks[0]->framework)->toBe('none');
        expect($result->stacks[0]->version)->toBe('8.4');
    });

    it('ignores Dockerfile with unknown base image', function () {
        $dockerfile = "FROM nginx:alpine\nEXPOSE 80";

        $client = \stubScannerGitClient(
            files: ['Dockerfile' => $dockerfile],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks)->toHaveCount(0);
    });

    it('detects Python from pyproject.toml with requires-python', function () {
        $pyproject = "[project]\nname = \"my-app\"\nrequires-python = \">=3.12\"\ndependencies = [\"fastapi\"]";

        $client = \stubScannerGitClient(
            files: ['pyproject.toml' => $pyproject],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks)->toHaveCount(1);
        expect($result->stacks[0]->language)->toBe('Python');
        expect($result->stacks[0]->framework)->toBe('FastAPI');
        expect($result->stacks[0]->version)->toBe('>=3.12');
    });

    it('enriches dependency URLs from composer.lock', function () {
        $composerJson = \json_encode([
            'require' => ['monolog/monolog' => '^3.0'],
        ]);
        $composerLock = \json_encode([
            'packages' => [
                [
                    'name' => 'monolog/monolog',
                    'version' => 'v3.8.0',
                    'source' => ['url' => 'https://github.com/Seldaek/monolog.git'],
                ],
            ],
            'packages-dev' => [],
        ]);

        $client = \stubScannerGitClient(
            files: ['composer.json' => $composerJson, 'composer.lock' => $composerLock],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->dependencies[0]->currentVersion)->toBe('3.8.0');
        expect($result->dependencies[0]->repositoryUrl)->toBe('https://github.com/Seldaek/monolog');
    });

    it('detects Nuxt framework from package.json', function () {
        $packageJson = \json_encode([
            'dependencies' => ['nuxt' => '^3.15.0', 'vue' => '^3.5.0'],
            'devDependencies' => ['typescript' => '^5.7.0'],
        ]);

        $client = \stubScannerGitClient(
            files: ['package.json' => $packageJson],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks[0]->framework)->toBe('Nuxt');
        expect($result->stacks[0]->frameworkVersion)->toBe('3.15.0');
        expect($result->stacks[0]->language)->toBe('TypeScript');
    });

    it('detects JavaScript without typescript dep', function () {
        $packageJson = \json_encode([
            'dependencies' => ['react' => '^18.0.0'],
        ]);

        $client = \stubScannerGitClient(
            files: ['package.json' => $packageJson],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks[0]->language)->toBe('JavaScript');
        expect($result->stacks[0]->framework)->toBe('React');
        expect($result->stacks[0]->frameworkVersion)->toBe('18.0.0');
    });

    it('extracts npm dep URLs', function () {
        $packageJson = \json_encode([
            'dependencies' => ['vue' => '^3.5.0'],
        ]);

        $client = \stubScannerGitClient(
            files: ['package.json' => $packageJson],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->dependencies[0]->repositoryUrl)->toBe('https://www.npmjs.com/package/vue');
    });

    it('detects Go without framework', function () {
        $goMod = "module example.com/app\n\ngo 1.21\n\nrequire golang.org/x/text v0.14.0\n";

        $client = \stubScannerGitClient(
            files: ['go.mod' => $goMod],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks[0]->framework)->toBe('none');
        expect($result->stacks[0]->version)->toBe('1.21');
    });

    it('filters php and ext- from composer dependencies', function () {
        $composerJson = \json_encode([
            'require' => [
                'php' => '>=8.4',
                'ext-json' => '*',
                'ext-mbstring' => '*',
                'doctrine/orm' => '^3.0',
            ],
        ]);

        $client = \stubScannerGitClient(
            files: ['composer.json' => $composerJson],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->dependencies)->toHaveCount(1);
        expect($result->dependencies[0]->name)->toBe('doctrine/orm');
    });

    it('detects PHP framework via symfony/ prefix fallback', function () {
        $composerJson = \json_encode([
            'require' => [
                'php' => '>=8.3',
                'symfony/http-foundation' => '^7.0',
            ],
        ]);

        $client = \stubScannerGitClient(
            files: ['composer.json' => $composerJson],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks[0]->framework)->toBe('Symfony');
        expect($result->stacks[0]->frameworkVersion)->toBe('7.0');
    });

    it('enriches PHP stack version from composer.lock platform', function () {
        $composerJson = \json_encode([
            'require' => ['php' => '>=8.3', 'symfony/framework-bundle' => '^7.2'],
        ]);
        $composerLock = \json_encode([
            'packages' => [
                ['name' => 'symfony/framework-bundle', 'version' => 'v7.2.5'],
            ],
            'packages-dev' => [],
            'platform' => ['php' => '8.4.2'],
        ]);

        $client = \stubScannerGitClient(
            files: ['composer.json' => $composerJson, 'composer.lock' => $composerLock],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks[0]->version)->toBe('8.4.2');
        expect($result->stacks[0]->frameworkVersion)->toBe('7.2.5');
    });

    it('preserves non-PHP stacks during composer.lock enrichment', function () {
        $composerJson = \json_encode([
            'require' => ['php' => '>=8.4', 'symfony/framework-bundle' => '^8.0'],
        ]);
        $composerLock = \json_encode([
            'packages' => [
                ['name' => 'symfony/framework-bundle', 'version' => 'v8.0.3'],
            ],
            'packages-dev' => [],
        ]);
        $packageJson = \json_encode([
            'dependencies' => ['vue' => '^3.5.0'],
        ]);

        $client = \stubScannerGitClient(
            files: [
                'backend/composer.json' => $composerJson,
                'backend/composer.lock' => $composerLock,
                'frontend/package.json' => $packageJson,
            ],
            tree: [
                '' => [
                    ['name' => 'backend', 'type' => 'tree', 'path' => 'backend'],
                    ['name' => 'frontend', 'type' => 'tree', 'path' => 'frontend'],
                ],
                'backend' => [
                    ['name' => 'composer.json', 'type' => 'blob', 'path' => 'backend/composer.json'],
                    ['name' => 'composer.lock', 'type' => 'blob', 'path' => 'backend/composer.lock'],
                ],
                'frontend' => [
                    ['name' => 'package.json', 'type' => 'blob', 'path' => 'frontend/package.json'],
                ],
            ],
        );

        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());
        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks)->toHaveCount(2);
        $jsStack = \array_values(\array_filter($result->stacks, fn ($s) => $s->language !== 'PHP'))[0];
        expect($jsStack->language)->toBe('JavaScript');
        expect($jsStack->framework)->toBe('Vue');
    });

    it('deduplicates with different package managers', function () {
        $composerJson = \json_encode([
            'require' => ['monolog/monolog' => '^3.0'],
        ]);
        $packageJson = \json_encode([
            'dependencies' => ['monolog' => '^1.0'],
        ]);

        $client = \stubScannerGitClient(
            files: ['composer.json' => $composerJson, 'package.json' => $packageJson],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        $monologs = \array_filter($result->dependencies, fn ($d) => \str_contains($d->name, 'monolog'));
        expect(\count($monologs))->toBe(2);
    });

    it('extracts pip deps with various version formats', function () {
        $requirements = "requests>=2.28.0\nflask~=3.0\nnumpy<2.0\nblack==24.1.0\nsetuptools\n";

        $client = \stubScannerGitClient(
            files: ['requirements.txt' => $requirements],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->dependencies)->toHaveCount(5);
        expect($result->dependencies[0]->name)->toBe('requests');
        expect($result->dependencies[0]->currentVersion)->toBe('2.28.0');
        expect($result->dependencies[1]->name)->toBe('flask');
        expect($result->dependencies[2]->name)->toBe('numpy');
        expect($result->dependencies[3]->name)->toBe('black');
        expect($result->dependencies[3]->currentVersion)->toBe('24.1.0');
        expect($result->dependencies[4]->name)->toBe('setuptools');
        expect($result->dependencies[4]->currentVersion)->toBe('*');
        expect($result->dependencies[0]->repositoryUrl)->toContain('pypi.org/project/requests');

        expect($result->stacks[0]->framework)->toBe('Flask');
    });

    it('detects Python without framework from requirements.txt', function () {
        $requirements = "requests>=2.28.0\n";

        $client = \stubScannerGitClient(
            files: ['requirements.txt' => $requirements],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks[0]->language)->toBe('Python');
        expect($result->stacks[0]->framework)->toBe('none');
        expect($result->stacks[0]->version)->toBe('');
        expect($result->stacks[0]->frameworkVersion)->toBe('');
    });

    it('detects Go Fiber framework', function () {
        $goMod = "module app\n\ngo 1.22\n\nrequire github.com/gofiber/fiber/v2 v2.52.0\n";

        $client = \stubScannerGitClient(
            files: ['go.mod' => $goMod],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks[0]->framework)->toBe('Fiber');
    });

    it('detects Rust Axum framework', function () {
        $cargoToml = "[package]\nname = \"api\"\nversion = \"1.0.0\"\n\n[dependencies]\naxum = \"0.7\"";

        $client = \stubScannerGitClient(
            files: ['Cargo.toml' => $cargoToml],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks[0]->framework)->toBe('Axum');
    });

    it('detects Ruby Sinatra framework', function () {
        $gemfile = "source 'https://rubygems.org'\ngem 'sinatra'";

        $client = \stubScannerGitClient(
            files: ['Gemfile' => $gemfile],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks[0]->framework)->toBe('Sinatra');
    });

    it('skips pyproject.toml when requirements.txt exists', function () {
        $requirements = "django==4.2\n";
        $pyproject = "[project]\nname = \"x\"\nrequires-python = \">=3.11\"";

        $client = \stubScannerGitClient(
            files: ['requirements.txt' => $requirements, 'pyproject.toml' => $pyproject],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        $pyStacks = \array_filter($result->stacks, fn ($s) => $s->language === 'Python');
        expect(\count($pyStacks))->toBe(1);
    });

    it('enriches composer.lock URLs using homepage fallback', function () {
        $composerJson = \json_encode([
            'require' => ['some/lib' => '^1.0'],
        ]);
        $composerLock = \json_encode([
            'packages' => [
                ['name' => 'some/lib', 'version' => '1.2.0', 'homepage' => 'https://example.com/some-lib'],
            ],
            'packages-dev' => [],
        ]);

        $client = \stubScannerGitClient(
            files: ['composer.json' => $composerJson, 'composer.lock' => $composerLock],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->dependencies[0]->repositoryUrl)->toBe('https://example.com/some-lib');
    });

    it('deduplicates PHP stack when Dockerfile and composer.json both detect PHP', function () {
        $composerJson = \json_encode([
            'require' => ['php' => '>=8.4', 'symfony/framework-bundle' => '^8.0'],
        ]);
        $dockerfile = "FROM php:8.4-fpm\nRUN apt-get update";

        $client = \stubScannerGitClient(
            files: ['composer.json' => $composerJson, 'Dockerfile' => $dockerfile],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks)->toHaveCount(1);
        expect($result->stacks[0]->language)->toBe('PHP');
        expect($result->stacks[0]->framework)->toBe('Symfony');
    });

    it('keeps only framework stacks when both framework and none exist for same language', function () {
        $composerJson = \json_encode([
            'require' => ['php' => '>=8.4', 'laravel/framework' => '^11.0'],
        ]);
        $dockerfile = "FROM php:8.4-cli\nCMD php artisan serve";

        $client = \stubScannerGitClient(
            files: [
                'composer.json' => $composerJson,
                'Dockerfile' => $dockerfile,
            ],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks)->toHaveCount(1);
        expect($result->stacks[0]->framework)->toBe('Laravel');
        expect($result->stacks[0]->language)->toBe('PHP');
    });

    it('keeps frameworkless stack when no framework is detected', function () {
        $dockerfile = "FROM node:22-alpine\nRUN npm install";

        $client = \stubScannerGitClient(
            files: ['Dockerfile' => $dockerfile],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks)->toHaveCount(1);
        expect($result->stacks[0]->language)->toBe('Node.js');
        expect($result->stacks[0]->framework)->toBe('none');
    });

    it('keeps stacks from different languages after dedup', function () {
        $composerJson = \json_encode([
            'require' => ['php' => '>=8.4', 'symfony/framework-bundle' => '^8.0'],
        ]);
        $packageJson = \json_encode([
            'dependencies' => ['vue' => '^3.5.0'],
            'devDependencies' => ['typescript' => '^5.7.0'],
        ]);
        $dockerfile = "FROM php:8.4-fpm\nRUN apt-get update";

        $client = \stubScannerGitClient(
            files: [
                'composer.json' => $composerJson,
                'package.json' => $packageJson,
                'Dockerfile' => $dockerfile,
            ],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks)->toHaveCount(2);
        $languages = \array_map(fn ($s) => $s->language, $result->stacks);
        expect($languages)->toContain('PHP');
        expect($languages)->toContain('TypeScript');

        $phpStack = \array_values(\array_filter($result->stacks, fn ($s) => $s->language === 'PHP'))[0];
        expect($phpStack->framework)->toBe('Symfony');
    });

    it('returns empty result when API throws instead of crashing', function () {
        $gitClient = new class () implements GitProviderInterface {
            public function listProjects(Provider $provider, int $page = 1, int $perPage = 20, ?string $search = null, ?string $visibility = null, string $sort = 'name', string $sortDir = 'asc'): array
            {
                return [];
            }
            public function countProjects(Provider $provider, ?string $search = null, ?string $visibility = null): int
            {
                return 0;
            }
            public function testConnection(Provider $provider): bool
            {
                return false;
            }
            public function getProject(Provider $provider, string $externalId): \App\Catalog\Domain\Model\RemoteProject
            {
                throw new \RuntimeException('Not implemented');
            }
            public function getFileContent(Provider $provider, string $externalProjectId, string $filePath, string $ref = 'main'): ?string
            {
                throw new \Symfony\Component\HttpClient\Exception\ClientException(
                    new \Symfony\Component\HttpClient\Response\MockResponse('', ['http_code' => 403]),
                );
            }
            public function listDirectory(Provider $provider, string $externalProjectId, string $path = '', string $ref = 'main'): array
            {
                throw new \Symfony\Component\HttpClient\Exception\ClientException(
                    new \Symfony\Component\HttpClient\Response\MockResponse('', ['http_code' => 403]),
                );
            }
            public function listBranches(Provider $provider, string $externalProjectId): array
            {
                return [];
            }
            public function listCommits(Provider $provider, string $externalProjectId, string $ref, ?\DateTimeImmutable $since = null, ?\DateTimeImmutable $until = null, int $perPage = 100): array
            {
                return [];
            }
        };

        $factory = \stubScannerFactory($gitClient);
        $scanner = new ProjectScanner($factory, \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks)->toBeEmpty();
        expect($result->dependencies)->toBeEmpty();
    });

    it('deduplicates dependencies across locations', function () {
        $packageJson = \json_encode([
            'dependencies' => ['vue' => '^3.5.0'],
        ]);

        $client = \stubScannerGitClient(
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

        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());
        $result = $scanner->scan(\createLinkedProject());

        $vueCount = \count(\array_filter($result->dependencies, fn ($d) => $d->name === 'vue'));
        expect($vueCount)->toBe(1);
    });

    it('enriches JS framework version from pnpm-lock.yaml', function () {
        $packageJson = \json_encode([
            'dependencies' => ['vue' => '^3.5.0', 'pinia' => '^3.0.0'],
            'devDependencies' => ['typescript' => '^5.7.0'],
        ]);
        $pnpmLock = <<<'YAML'
lockfileVersion: '9.0'

importers:
  .:
    dependencies:
      vue:
        specifier: ^3.5.0
        version: 3.5.13
      pinia:
        specifier: ^3.0.0
        version: 3.0.2
    devDependencies:
      typescript:
        specifier: ^5.7.0
        version: 5.7.3
YAML;

        $client = \stubScannerGitClient(
            files: ['package.json' => $packageJson, 'pnpm-lock.yaml' => $pnpmLock],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks[0]->frameworkVersion)->toBe('3.5.13');
        expect($result->stacks[0]->version)->toBe('5.7.3');

        $vueDep = \array_values(\array_filter($result->dependencies, fn ($d) => $d->name === 'vue'))[0];
        expect($vueDep->currentVersion)->toBe('3.5.13');
        $tsDep = \array_values(\array_filter($result->dependencies, fn ($d) => $d->name === 'typescript'))[0];
        expect($tsDep->currentVersion)->toBe('5.7.3');
    });

    it('enriches Nuxt version from pnpm-lock.yaml with peer deps', function () {
        $packageJson = \json_encode([
            'dependencies' => ['nuxt' => '^3'],
            'devDependencies' => ['typescript' => '^5.7.0'],
        ]);
        $pnpmLock = <<<'YAML'
lockfileVersion: '9.0'

importers:
  .:
    dependencies:
      nuxt:
        specifier: ^3
        version: 3.16.2(@types/node@22.15.3)(typescript@5.7.3)
    devDependencies:
      typescript:
        specifier: ^5.7.0
        version: 5.7.3
YAML;

        $client = \stubScannerGitClient(
            files: ['package.json' => $packageJson, 'pnpm-lock.yaml' => $pnpmLock],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks[0]->framework)->toBe('Nuxt');
        expect($result->stacks[0]->frameworkVersion)->toBe('3.16.2');
    });

    it('enriches JS framework version from package-lock.json', function () {
        $packageJson = \json_encode([
            'dependencies' => ['react' => '^18.0.0'],
        ]);
        $npmLock = \json_encode([
            'lockfileVersion' => 3,
            'packages' => [
                '' => ['name' => 'my-app'],
                'node_modules/react' => ['version' => '18.3.1'],
            ],
        ]);

        $client = \stubScannerGitClient(
            files: ['package.json' => $packageJson, 'package-lock.json' => $npmLock],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks[0]->frameworkVersion)->toBe('18.3.1');

        $reactDep = \array_values(\array_filter($result->dependencies, fn ($d) => $d->name === 'react'))[0];
        expect($reactDep->currentVersion)->toBe('18.3.1');
    });

    it('enriches JS framework version from yarn.lock', function () {
        $packageJson = \json_encode([
            'dependencies' => ['nuxt' => '^3'],
            'devDependencies' => ['typescript' => '^5.7.0'],
        ]);
        $yarnLock = <<<'YARN'
__metadata:
  version: 8

"nuxt@npm:3":
  version: 3.21.1
  resolution: "nuxt@npm:3.21.1"
  dependencies:
    vue: "npm:^3.5.0"

"typescript@npm:^5.7.0":
  version: 5.8.3
  resolution: "typescript@npm:5.8.3"

"vue@npm:^3.5.0":
  version: 3.5.13
  resolution: "vue@npm:3.5.13"
YARN;

        $client = \stubScannerGitClient(
            files: ['package.json' => $packageJson, 'yarn.lock' => $yarnLock],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks[0]->framework)->toBe('Nuxt');
        expect($result->stacks[0]->frameworkVersion)->toBe('3.21.1');
        expect($result->stacks[0]->version)->toBe('5.8.3');

        $nuxtDep = \array_values(\array_filter($result->dependencies, fn ($d) => $d->name === 'nuxt'))[0];
        expect($nuxtDep->currentVersion)->toBe('3.21.1');
    });

    it('enriches JS dep versions from subdirectory pnpm-lock.yaml', function () {
        $packageJson = \json_encode([
            'dependencies' => ['vue' => '^3.5.0'],
            'devDependencies' => ['typescript' => '^5.7.0'],
        ]);
        $pnpmLock = <<<'YAML'
lockfileVersion: '9.0'

importers:
  .:
    dependencies:
      vue:
        specifier: ^3.5.0
        version: 3.5.13
    devDependencies:
      typescript:
        specifier: ^5.7.0
        version: 5.7.3
YAML;

        $client = \stubScannerGitClient(
            files: [
                'frontend/package.json' => $packageJson,
                'frontend/pnpm-lock.yaml' => $pnpmLock,
            ],
            tree: [
                '' => [
                    ['name' => 'frontend', 'type' => 'tree', 'path' => 'frontend'],
                ],
                'frontend' => [
                    ['name' => 'package.json', 'type' => 'blob', 'path' => 'frontend/package.json'],
                    ['name' => 'pnpm-lock.yaml', 'type' => 'blob', 'path' => 'frontend/pnpm-lock.yaml'],
                ],
            ],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        $vueDep = \array_values(\array_filter($result->dependencies, fn ($d) => $d->name === 'vue'))[0];
        expect($vueDep->currentVersion)->toBe('3.5.13');
    });

    it('handles npm lock with invalid JSON gracefully', function () {
        $packageJson = \json_encode([
            'dependencies' => ['react' => '^18.0.0'],
        ]);

        $client = \stubScannerGitClient(
            files: [
                'package.json' => $packageJson,
                'package-lock.json' => 'not valid json',
            ],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks)->toHaveCount(1);
        expect($result->dependencies[0]->name)->toBe('react');
        expect($result->dependencies[0]->currentVersion)->toBe('18.0.0');
    });

    it('handles composer.lock with invalid JSON gracefully', function () {
        $composerJson = \json_encode([
            'require' => ['symfony/framework-bundle' => '^8.0'],
        ]);

        $client = \stubScannerGitClient(
            files: [
                'composer.json' => $composerJson,
                'composer.lock' => 'not valid json',
            ],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks[0]->framework)->toBe('Symfony');
        expect($result->dependencies[0]->currentVersion)->toBe('8.0');
    });

    it('deduplicates stacks with two different frameworks for same language', function () {
        $composerJson1 = \json_encode([
            'require' => ['php' => '>=8.4', 'symfony/framework-bundle' => '^8.0'],
        ]);
        $composerJson2 = \json_encode([
            'require' => ['php' => '>=8.4', 'laravel/framework' => '^11.0'],
        ]);

        $client = \stubScannerGitClient(
            files: [
                'api/composer.json' => $composerJson1,
                'admin/composer.json' => $composerJson2,
            ],
            tree: [
                '' => [
                    ['name' => 'api', 'type' => 'tree', 'path' => 'api'],
                    ['name' => 'admin', 'type' => 'tree', 'path' => 'admin'],
                ],
                'api' => [
                    ['name' => 'composer.json', 'type' => 'blob', 'path' => 'api/composer.json'],
                ],
                'admin' => [
                    ['name' => 'composer.json', 'type' => 'blob', 'path' => 'admin/composer.json'],
                ],
            ],
        );

        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());
        $result = $scanner->scan(\createLinkedProject());

        $phpStacks = \array_filter($result->stacks, fn ($s) => $s->language === 'PHP');
        expect(\count($phpStacks))->toBe(2);

        $frameworks = \array_map(fn ($s) => $s->framework, \array_values($phpStacks));
        expect($frameworks)->toContain('Symfony');
        expect($frameworks)->toContain('Laravel');
    });

    it('deduplicates same framework for same language', function () {
        $composerJson1 = \json_encode([
            'require' => ['php' => '>=8.4', 'symfony/framework-bundle' => '^8.0'],
        ]);
        $composerJson2 = \json_encode([
            'require' => ['php' => '>=8.3', 'symfony/framework-bundle' => '^7.0'],
        ]);

        $client = \stubScannerGitClient(
            files: [
                'api/composer.json' => $composerJson1,
                'admin/composer.json' => $composerJson2,
            ],
            tree: [
                '' => [
                    ['name' => 'api', 'type' => 'tree', 'path' => 'api'],
                    ['name' => 'admin', 'type' => 'tree', 'path' => 'admin'],
                ],
                'api' => [
                    ['name' => 'composer.json', 'type' => 'blob', 'path' => 'api/composer.json'],
                ],
                'admin' => [
                    ['name' => 'composer.json', 'type' => 'blob', 'path' => 'admin/composer.json'],
                ],
            ],
        );

        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());
        $result = $scanner->scan(\createLinkedProject());

        $phpStacks = \array_filter($result->stacks, fn ($s) => $s->language === 'PHP');
        expect(\count($phpStacks))->toBe(1);
        expect(\array_values($phpStacks)[0]->framework)->toBe('Symfony');
    });

    it('skips non-manifest files in subdirectory discovery', function () {
        $client = \stubScannerGitClient(
            files: [],
            tree: [
                '' => [
                    ['name' => 'docs', 'type' => 'tree', 'path' => 'docs'],
                    ['name' => 'README.md', 'type' => 'blob', 'path' => 'README.md'],
                ],
                'docs' => [
                    ['name' => 'guide.md', 'type' => 'blob', 'path' => 'docs/guide.md'],
                    ['name' => 'images', 'type' => 'tree', 'path' => 'docs/images'],
                ],
            ],
        );

        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());
        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks)->toBeEmpty();
        expect($result->dependencies)->toBeEmpty();
    });

    it('enriches PHP stack version from platform-overrides in composer.lock', function () {
        $composerJson = \json_encode([
            'require' => ['php' => '>=8.3', 'symfony/framework-bundle' => '^7.2'],
        ]);
        $composerLock = \json_encode([
            'packages' => [
                ['name' => 'symfony/framework-bundle', 'version' => 'v7.2.5'],
            ],
            'packages-dev' => [],
            'platform' => ['php' => '8.3.0'],
            'platform-overrides' => ['php' => '8.4.1'],
        ]);

        $client = \stubScannerGitClient(
            files: ['composer.json' => $composerJson, 'composer.lock' => $composerLock],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks[0]->version)->toBe('8.4.1');
    });

    it('does not override PHP version when composer.lock has no platform', function () {
        $composerJson = \json_encode([
            'require' => ['php' => '>=8.4', 'symfony/framework-bundle' => '^8.0'],
        ]);
        $composerLock = \json_encode([
            'packages' => [
                ['name' => 'symfony/framework-bundle', 'version' => 'v8.0.3'],
            ],
            'packages-dev' => [],
        ]);

        $client = \stubScannerGitClient(
            files: ['composer.json' => $composerJson, 'composer.lock' => $composerLock],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks[0]->version)->toBe('8.4');
    });

    it('enriches PHP framework version via symfony prefix fallback from lock', function () {
        $composerJson = \json_encode([
            'require' => [
                'php' => '>=5.6',
                'symfony/http-foundation' => '2.8.*',
            ],
        ]);
        $composerLock = \json_encode([
            'packages' => [
                ['name' => 'symfony/http-foundation', 'version' => 'v2.8.52'],
            ],
            'packages-dev' => [],
        ]);

        $client = \stubScannerGitClient(
            files: ['composer.json' => $composerJson, 'composer.lock' => $composerLock],
            tree: ['' => []],
        );
        $scanner = new ProjectScanner(\stubScannerFactory($client), \allDetectors());

        $result = $scanner->scan(\createLinkedProject());

        expect($result->stacks[0]->framework)->toBe('Symfony');
        expect($result->stacks[0]->frameworkVersion)->toBe('2.8.52');
    });
});
