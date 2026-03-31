<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Model\ProviderType;
use App\Coverage\Infrastructure\Provider\GitHubCoverageProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Tests\Factory\Catalog\ProviderFactory;

function makeGitHubProject(
    string $defaultBranch = 'main',
    string $repositoryUrl = 'https://github.com/myorg/myrepo',
): Project {
    $provider = ProviderFactory::create(
        name: 'GitHub Test',
        type: ProviderType::GitHub,
        url: 'https://api.github.com',
        apiToken: 'ghp-test-token',
    );

    return Project::create(
        name: 'My GitHub Project',
        slug: 'my-github-project',
        description: null,
        repositoryUrl: $repositoryUrl,
        defaultBranch: $defaultBranch,
        visibility: ProjectVisibility::Private,
        ownerId: Uuid::v7(),
        provider: $provider,
    );
}

function stubSequentialHttpClient(array $responses): HttpClientInterface
{
    return new class ($responses) implements HttpClientInterface {
        private int $callIndex = 0;

        public function __construct(private readonly array $responses)
        {
        }

        public function request(string $method, string $url, array $options = []): ResponseInterface
        {
            $data = $this->responses[$this->callIndex] ?? [];
            ++$this->callIndex;

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

function stubThrowingGitHubHttpClient(\Throwable $exception): HttpClientInterface
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

describe('GitHubCoverageProvider', function (): void {
    describe('supports()', function (): void {
        it('returns true for GitHub', function (): void {
            $provider = new GitHubCoverageProvider(
                stubSequentialHttpClient([]),
                new NullLogger(),
            );

            expect($provider->supports(ProviderType::GitHub))->toBeTrue();
        });

        it('returns false for GitLab', function (): void {
            $provider = new GitHubCoverageProvider(
                stubSequentialHttpClient([]),
                new NullLogger(),
            );

            expect($provider->supports(ProviderType::GitLab))->toBeFalse();
        });
    });

    describe('fetchCoverage()', function (): void {
        it('fetches coverage from check run output summary', function (): void {
            $http = stubSequentialHttpClient([
                [
                    'workflow_runs' => [
                        [
                            'id' => 99,
                            'head_sha' => 'deadbeef1234',
                            'head_branch' => 'main',
                        ],
                    ],
                ],
                [
                    'check_runs' => [
                        [
                            'output' => [
                                'summary' => 'All tests passed. Coverage: 92.3%',
                                'text' => null,
                            ],
                        ],
                    ],
                ],
            ]);

            $project = makeGitHubProject(defaultBranch: 'main');
            $coverageProvider = new GitHubCoverageProvider($http, new NullLogger());
            $result = $coverageProvider->fetchCoverage($project);

            expect($result)->not->toBeNull();
            expect($result->coveragePercent)->toBe(92.3);
            expect($result->commitHash)->toBe('deadbeef1234');
            expect($result->ref)->toBe('main');
            expect($result->pipelineId)->toBe('99');
        });

        it('returns null when no coverage pattern found in check runs', function (): void {
            $http = stubSequentialHttpClient([
                [
                    'workflow_runs' => [
                        [
                            'id' => 12,
                            'head_sha' => 'abc000',
                            'head_branch' => 'main',
                        ],
                    ],
                ],
                [
                    'check_runs' => [
                        [
                            'output' => [
                                'summary' => 'Build successful, no metrics reported.',
                                'text' => null,
                            ],
                        ],
                    ],
                ],
            ]);

            $project = makeGitHubProject();
            $coverageProvider = new GitHubCoverageProvider($http, new NullLogger());

            expect($coverageProvider->fetchCoverage($project))->toBeNull();
        });

        it('returns null when no workflow runs found', function (): void {
            $http = stubSequentialHttpClient([
                ['workflow_runs' => []],
            ]);

            $project = makeGitHubProject();
            $coverageProvider = new GitHubCoverageProvider($http, new NullLogger());

            expect($coverageProvider->fetchCoverage($project))->toBeNull();
        });

        it('extracts owner and repo from repository URL with .git suffix', function (): void {
            $http = stubSequentialHttpClient([
                [
                    'workflow_runs' => [
                        [
                            'id' => 55,
                            'head_sha' => 'cafebabe',
                            'head_branch' => 'main',
                        ],
                    ],
                ],
                [
                    'check_runs' => [
                        [
                            'output' => [
                                'summary' => 'coverage: 78%',
                                'text' => null,
                            ],
                        ],
                    ],
                ],
            ]);

            $project = makeGitHubProject(
                repositoryUrl: 'https://github.com/acme-corp/awesome-project.git',
            );
            $coverageProvider = new GitHubCoverageProvider($http, new NullLogger());
            $result = $coverageProvider->fetchCoverage($project);

            expect($result)->not->toBeNull();
            expect($result->coveragePercent)->toBe(78.0);
        });

        it('returns null and logs warning on API exception', function (): void {
            $http = stubThrowingGitHubHttpClient(new \RuntimeException('connection refused'));

            $logger = $this->createMock(LoggerInterface::class);
            $logger->expects($this->once())
                ->method('warning')
                ->with(
                    'GitHub coverage fetch failed.',
                    $this->arrayHasKey('error'),
                );

            $project = makeGitHubProject();
            $coverageProvider = new GitHubCoverageProvider($http, $logger);

            expect($coverageProvider->fetchCoverage($project))->toBeNull();
        });
    });
});
