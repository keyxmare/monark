<?php

declare(strict_types=1);

namespace App\Coverage\Infrastructure\Provider;

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProviderType;
use App\Coverage\Domain\Port\CoverageProviderInterface;
use App\Coverage\Domain\ValueObject\CoverageResult;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

#[AutoconfigureTag('app.coverage_provider')]
final readonly class GitHubCoverageProvider implements CoverageProviderInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    public function supports(ProviderType $type): bool
    {
        return $type === ProviderType::GitHub;
    }

    public function fetchCoverage(Project $project): ?CoverageResult
    {
        try {
            $provider = $project->getProvider();

            if ($provider === null) {
                return null;
            }

            $repoPath = $this->extractOwnerRepo($project->getRepositoryUrl());

            if ($repoPath === null) {
                return null;
            }

            $baseUrl = $provider->getUrl();
            $token = $provider->getApiToken();
            $headers = ['Authorization' => 'Bearer ' . $token];

            $runsUrl = \sprintf('%s/repos/%s/actions/runs', $baseUrl, $repoPath);

            $runsResponse = $this->httpClient->request('GET', $runsUrl, [
                'headers' => $headers,
                'query' => [
                    'branch' => $project->getDefaultBranch(),
                    'status' => 'success',
                    'per_page' => 1,
                ],
                'timeout' => 15,
            ]);

            /** @var array{workflow_runs?: list<array{head_sha?: string, head_branch?: string, id?: int|string}>} $runsData */
            $runsData = $runsResponse->toArray();

            if (empty($runsData['workflow_runs'])) {
                return null;
            }

            $run = $runsData['workflow_runs'][0];
            $sha = (string) ($run['head_sha'] ?? '');
            $ref = (string) ($run['head_branch'] ?? $project->getDefaultBranch());
            $runId = isset($run['id']) ? (string) $run['id'] : null;

            $checkRunsUrl = \sprintf('%s/repos/%s/check-runs', $baseUrl, $repoPath);

            $checkRunsResponse = $this->httpClient->request('GET', $checkRunsUrl, [
                'headers' => $headers,
                'query' => ['head_sha' => $sha],
                'timeout' => 15,
            ]);

            /** @var array{check_runs?: list<array{output?: array{summary?: string|null, text?: string|null}}>} $checkRunsData */
            $checkRunsData = $checkRunsResponse->toArray();

            $percent = $this->extractCoveragePercent($checkRunsData['check_runs'] ?? []);

            if ($percent === null) {
                return null;
            }

            return new CoverageResult(
                coveragePercent: $percent,
                commitHash: $sha,
                ref: $ref,
                pipelineId: $runId,
            );
        } catch (Throwable $e) {
            $this->logger->warning('GitHub coverage fetch failed.', [
                'project' => $project->getName(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function extractOwnerRepo(string $repositoryUrl): ?string
    {
        $path = \parse_url($repositoryUrl, \PHP_URL_PATH);

        if ($path === null || $path === false || $path === '') {
            return null;
        }

        $path = \ltrim($path, '/');
        $path = \preg_replace('/\.git$/', '', $path);

        if ($path === null || $path === '') {
            return null;
        }

        $parts = \explode('/', $path);

        if (\count($parts) < 2) {
            return null;
        }

        return $parts[0] . '/' . $parts[1];
    }

    /**
     * @param list<array{output?: array{summary?: string|null, text?: string|null}}> $checkRuns
     */
    private function extractCoveragePercent(array $checkRuns): ?float
    {
        foreach ($checkRuns as $checkRun) {
            $output = $checkRun['output'] ?? [];

            foreach (['summary', 'text'] as $field) {
                $content = $output[$field] ?? null;

                if (!\is_string($content) || $content === '') {
                    continue;
                }

                if (\preg_match('/coverage[:\s]+(\d+\.?\d*)%/i', $content, $matches)) {
                    return (float) $matches[1];
                }
            }
        }

        return null;
    }
}
