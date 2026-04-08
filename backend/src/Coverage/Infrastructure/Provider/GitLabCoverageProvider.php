<?php

declare(strict_types=1);

namespace App\Coverage\Infrastructure\Provider;

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProviderType;
use App\Coverage\Domain\Port\CoverageProviderInterface;
use App\Coverage\Domain\ValueObject\CoverageResult;
use App\Coverage\Domain\ValueObject\JobCoverage;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

#[AutoconfigureTag('app.coverage_provider')]
final readonly class GitLabCoverageProvider implements CoverageProviderInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    public function supports(ProviderType $type): bool
    {
        return $type === ProviderType::GitLab;
    }

    public function fetchCoverage(Project $project): ?CoverageResult
    {
        try {
            $provider = $project->getProvider();

            if ($provider === null) {
                return null;
            }

            $baseUrl = $provider->getUrl();
            $externalId = \rawurlencode((string) $project->getExternalId());
            $headers = ['PRIVATE-TOKEN' => $provider->getApiToken()];

            $listResponse = $this->httpClient->request('GET', \sprintf(
                '%s/api/v4/projects/%s/pipelines',
                $baseUrl,
                $externalId,
            ), [
                'headers' => $headers,
                'query' => [
                    'ref' => $project->getDefaultBranch(),
                    'status' => 'success',
                    'per_page' => 1,
                ],
                'timeout' => 15,
            ]);

            /** @var list<array{id?: int|string, coverage?: string|float|null, sha?: string}> $pipelines */
            $pipelines = $listResponse->toArray();

            if ($pipelines === []) {
                return null;
            }

            $pipelineId = $pipelines[0]['id'] ?? null;
            if ($pipelineId === null) {
                return null;
            }

            $detailResponse = $this->httpClient->request('GET', \sprintf(
                '%s/api/v4/projects/%s/pipelines/%s',
                $baseUrl,
                $externalId,
                (string) $pipelineId,
            ), [
                'headers' => $headers,
                'timeout' => 15,
            ]);

            /** @var array{coverage?: string|float|null, sha?: string} $pipeline */
            $pipeline = $detailResponse->toArray();
            $coverage = $pipeline['coverage'] ?? null;

            if ($coverage === null) {
                return null;
            }

            $jobsResponse = $this->httpClient->request('GET', \sprintf(
                '%s/api/v4/projects/%s/pipelines/%s/jobs',
                $baseUrl,
                $externalId,
                (string) $pipelineId,
            ), [
                'headers' => $headers,
                'query' => ['per_page' => 100],
                'timeout' => 15,
            ]);

            /** @var list<array{name?: string, coverage?: string|float|null}> $jobsData */
            $jobsData = $jobsResponse->toArray();

            $jobs = [];
            foreach ($jobsData as $job) {
                $jobCoverage = $job['coverage'] ?? null;
                if ($jobCoverage !== null) {
                    $jobs[] = new JobCoverage(
                        name: (string) ($job['name'] ?? 'unknown'),
                        percent: (float) $jobCoverage,
                    );
                }
            }

            $jobs = $this->cleanJobNames($jobs);

            return new CoverageResult(
                coveragePercent: (float) $coverage,
                commitHash: (string) ($pipeline['sha'] ?? ''),
                ref: $project->getDefaultBranch(),
                pipelineId: (string) $pipelineId,
                jobs: $jobs,
            );
        } catch (Throwable $e) {
            $this->logger->warning('GitLab coverage fetch failed.', [
                'project' => $project->getName(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /** @param list<JobCoverage> $jobs
     * @return list<JobCoverage> */
    private function cleanJobNames(array $jobs): array
    {
        if (\count($jobs) <= 1) {
            return $jobs;
        }

        $names = \array_map(static fn (JobCoverage $j): string => $j->name, $jobs);
        $prefix = $this->longestCommonPrefix($names);

        if ($prefix === '') {
            return $jobs;
        }

        return \array_map(
            static fn (JobCoverage $j): JobCoverage => new JobCoverage(
                name: \substr($j->name, \strlen($prefix)),
                percent: $j->percent,
            ),
            $jobs,
        );
    }

    /** @param list<string> $strings */
    private function longestCommonPrefix(array $strings): string
    {
        if ($strings === []) {
            return '';
        }

        $first = $strings[0];
        $prefixLen = \strlen($first);

        foreach ($strings as $s) {
            $prefixLen = \min($prefixLen, \strlen($s));
            for ($i = 0; $i < $prefixLen; $i++) {
                if ($s[$i] !== $first[$i]) {
                    $prefixLen = $i;
                    break;
                }
            }
        }

        $prefix = \substr($first, 0, $prefixLen);
        $lastSep = \max(\strrpos($prefix, ':') ?: 0, \strrpos($prefix, '/') ?: 0);

        if ($lastSep === 0) {
            return '';
        }

        return \substr($prefix, 0, $lastSep + 1);
    }
}
