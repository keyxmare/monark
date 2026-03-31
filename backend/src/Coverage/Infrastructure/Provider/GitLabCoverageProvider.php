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

            $url = \sprintf(
                '%s/api/v4/projects/%s/pipelines',
                $provider->getUrl(),
                \rawurlencode((string) $project->getExternalId()),
            );

            $response = $this->httpClient->request('GET', $url, [
                'headers' => ['PRIVATE-TOKEN' => $provider->getApiToken()],
                'query' => [
                    'ref' => $project->getDefaultBranch(),
                    'status' => 'success',
                    'per_page' => 1,
                ],
                'timeout' => 15,
            ]);

            /** @var list<array{id?: int|string, sha?: string, coverage?: float|null}> $pipelines */
            $pipelines = $response->toArray();

            if ($pipelines === []) {
                return null;
            }

            $pipeline = $pipelines[0];

            if (!isset($pipeline['coverage']) || $pipeline['coverage'] === null) {
                return null;
            }

            return new CoverageResult(
                coveragePercent: (float) $pipeline['coverage'],
                commitHash: (string) ($pipeline['sha'] ?? ''),
                ref: $project->getDefaultBranch(),
                pipelineId: isset($pipeline['id']) ? (string) $pipeline['id'] : null,
            );
        } catch (Throwable $e) {
            $this->logger->warning('GitLab coverage fetch failed.', [
                'project' => $project->getName(),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
