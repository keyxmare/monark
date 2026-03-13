<?php

declare(strict_types=1);

namespace App\Activity\Application\DTO;

use App\Activity\Domain\Model\BuildMetric;
use DateTimeInterface;

final readonly class BuildMetricOutput
{
    public function __construct(
        public string $id,
        public string $projectId,
        public string $commitSha,
        public string $ref,
        public ?float $backendCoverage,
        public ?float $frontendCoverage,
        public ?float $mutationScore,
        public string $createdAt,
    ) {
    }

    public static function fromEntity(BuildMetric $buildMetric): self
    {
        return new self(
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
