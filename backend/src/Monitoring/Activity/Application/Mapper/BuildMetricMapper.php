<?php

declare(strict_types=1);

namespace App\Monitoring\Activity\Application\Mapper;

use App\Monitoring\Activity\Application\DTO\BuildMetricOutput;
use App\Monitoring\Activity\Domain\Model\BuildMetric;
use DateTimeInterface;

final class BuildMetricMapper
{
    public static function toOutput(BuildMetric $buildMetric): BuildMetricOutput
    {
        return new BuildMetricOutput(
            id: $buildMetric->getId()->toRfc4122(),
            projectId: $buildMetric->getProjectId()->toRfc4122(),
            commitSha: $buildMetric->getCommitSha(),
            ref: $buildMetric->getRef(),
            backendCoverage: $buildMetric->getBackendCoverage(),
            frontendCoverage: $buildMetric->getFrontendCoverage(),
            mutationScore: $buildMetric->getMutationScore(),
            createdAt: $buildMetric->getCreatedAt()->format(DateTimeInterface::ATOM),
        );
    }
}
