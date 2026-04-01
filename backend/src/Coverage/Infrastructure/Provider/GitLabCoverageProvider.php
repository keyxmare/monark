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
                $pipelineId,
            ), [
                'headers' => $headers,
                'timeout' => 15,
            ]);

            $pipeline = $detailResponse->toArray();
            $coverage = $pipeline['coverage'] ?? null;

            if ($coverage === null) {
                return null;
            }

            return new CoverageResult(
                coveragePercent: (float) $coverage,
                commitHash: (string) ($pipeline['sha'] ?? ''),
                ref: $project->getDefaultBranch(),
                pipelineId: (string) $pipelineId,
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
