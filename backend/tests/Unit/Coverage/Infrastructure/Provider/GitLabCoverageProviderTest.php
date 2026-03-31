<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Model\ProviderType;
use App\Coverage\Infrastructure\Provider\GitLabCoverageProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Tests\Factory\Catalog\ProviderFactory;

function makeGitLabProject(string $defaultBranch = 'main', ?string $externalId = '123'): Project
{
    $provider = ProviderFactory::create(
        type: ProviderType::GitLab,
        url: 'https://gitlab.example.com',
        apiToken: 'glpat-test-token',
    );

    return Project::create(
        name: 'My Project',
        slug: 'my-project',
        description: null,
        repositoryUrl: 'https://gitlab.example.com/test/project',
        defaultBranch: $defaultBranch,
        visibility: ProjectVisibility::Private,
        ownerId: Uuid::v7(),
        provider: $provider,
        externalId: $externalId,
    );
}

function stubHttpClient(array $responseData): HttpClientInterface
{
    return new class ($responseData) implements HttpClientInterface {
        public function __construct(private readonly array $responseData)
        {
        }

        public function request(string $method, string $url, array $options = []): ResponseInterface
        {
            $data = $this->responseData;

            return new class ($data) implements ResponseInterface {
                public function __construct(private readonly array $data)
                {
                }

                public function getStatusCode(): int { return 200; }

                public function getHeaders(bool $throw = true): array { return []; }

                public function getContent(bool $throw = true): string { return ''; }

                public function toArray(bool $throw = true): array { return $this->data; }

                public function cancel(): void {}

                public function getInfo(?string $type = null): mixed { return null; }
            };
        }

        public function stream(iterable|ResponseInterface $responses, ?float $timeout = null): ResponseStreamInterface
        {
            throw new \RuntimeException('Not implemented');
        }

        public function withOptions(array $options): static
        {
            return $this;
        }
    };
}

function stubThrowingHttpClient(\Throwable $exception): HttpClientInterface
{
    return new class ($exception) implements HttpClientInterface {
        public function __construct(private readonly \Throwable $exception)
        {
        }

        public function request(string $method, string $url, array $options = []): ResponseInterface
        {
            throw $this->exception;
        }

        public function stream(iterable|ResponseInterface $responses, ?float $timeout = null): ResponseStreamInterface
        {
            throw new \RuntimeException('Not implemented');
        }

        public function withOptions(array $options): static
        {
            return $this;
        }
    };
}

describe('GitLabCoverageProvider', function (): void {
    describe('supports()', function (): void {
        it('returns true for GitLab', function (): void {
            $provider = new GitLabCoverageProvider(
                stubHttpClient([]),
                new NullLogger(),
            );

            expect($provider->supports(ProviderType::GitLab))->toBeTrue();
        });

        it('returns false for GitHub', function (): void {
            $provider = new GitLabCoverageProvider(
                stubHttpClient([]),
                new NullLogger(),
            );

            expect($provider->supports(ProviderType::GitHub))->toBeFalse();
        });
    });

    describe('fetchCoverage()', function (): void {
        it('returns a CoverageResult when pipeline has coverage', function (): void {
            $http = stubHttpClient([
                [
                    'id' => 42,
                    'sha' => 'abc123def456',
                    'coverage' => 87.5,
                ],
            ]);

            $project = makeGitLabProject(defaultBranch: 'main', externalId: '123');
            $coverageProvider = new GitLabCoverageProvider($http, new NullLogger());
            $result = $coverageProvider->fetchCoverage($project);

            expect($result)->not->toBeNull();
            expect($result->coveragePercent)->toBe(87.5);
            expect($result->commitHash)->toBe('abc123def456');
            expect($result->ref)->toBe('main');
            expect($result->pipelineId)->toBe('42');
        });

        it('returns null when pipeline has null coverage', function (): void {
            $http = stubHttpClient([
                [
                    'id' => 42,
                    'sha' => 'abc123def456',
                    'coverage' => null,
                ],
            ]);

            $project = makeGitLabProject();
            $coverageProvider = new GitLabCoverageProvider($http, new NullLogger());

            expect($coverageProvider->fetchCoverage($project))->toBeNull();
        });

        it('returns null when no pipelines found', function (): void {
            $http = stubHttpClient([]);

            $project = makeGitLabProject();
            $coverageProvider = new GitLabCoverageProvider($http, new NullLogger());

            expect($coverageProvider->fetchCoverage($project))->toBeNull();
        });

        it('returns null and logs warning on API exception', function (): void {
            $http = stubThrowingHttpClient(new \RuntimeException('connection refused'));

            $logger = $this->createMock(LoggerInterface::class);
            $logger->expects($this->once())
                ->method('warning')
                ->with(
                    'GitLab coverage fetch failed.',
                    $this->arrayHasKey('error'),
                );

            $project = makeGitLabProject();
            $coverageProvider = new GitLabCoverageProvider($http, $logger);

            expect($coverageProvider->fetchCoverage($project))->toBeNull();
        });
    });
});
