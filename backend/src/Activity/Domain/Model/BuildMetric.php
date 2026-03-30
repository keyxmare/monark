<?php

declare(strict_types=1);

namespace App\Activity\Domain\Model;

use App\Activity\Domain\Event\BuildMetricRecorded;
use App\Shared\Domain\Model\RecordsDomainEvents;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'activity_build_metrics')]
#[ORM\Index(columns: ['project_id', 'created_at'], name: 'idx_build_metric_project_date')]
final class BuildMetric
{
    use RecordsDomainEvents;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'uuid')]
    private Uuid $projectId;

    #[ORM\Column(length: 40)]
    private string $commitSha;

    #[ORM\Column(length: 255)]
    private string $ref;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $backendCoverage;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $frontendCoverage;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $mutationScore;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    private function __construct(
        Uuid $id,
        Uuid $projectId,
        string $commitSha,
        string $ref,
        ?float $backendCoverage,
        ?float $frontendCoverage,
        ?float $mutationScore,
    ) {
        $this->id = $id;
        $this->projectId = $projectId;
        $this->commitSha = $commitSha;
        $this->ref = $ref;
        $this->backendCoverage = $backendCoverage;
        $this->frontendCoverage = $frontendCoverage;
        $this->mutationScore = $mutationScore;
        $this->createdAt = new DateTimeImmutable();
    }

    public static function create(
        Uuid $projectId,
        string $commitSha,
        string $ref,
        ?float $backendCoverage = null,
        ?float $frontendCoverage = null,
        ?float $mutationScore = null,
    ): self {
        $metric = new self(
            id: Uuid::v7(),
            projectId: $projectId,
            commitSha: $commitSha,
            ref: $ref,
            backendCoverage: $backendCoverage,
            frontendCoverage: $frontendCoverage,
            mutationScore: $mutationScore,
        );

        $metric->recordEvent(new BuildMetricRecorded(
            metricId: $metric->id->toRfc4122(),
            commitSha: $metric->commitSha,
            ref: $metric->ref,
            createdAt: $metric->createdAt,
        ));

        return $metric;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getProjectId(): Uuid
    {
        return $this->projectId;
    }

    public function getCommitSha(): string
    {
        return $this->commitSha;
    }

    public function getRef(): string
    {
        return $this->ref;
    }

    public function getBackendCoverage(): ?float
    {
        return $this->backendCoverage;
    }

    public function getFrontendCoverage(): ?float
    {
        return $this->frontendCoverage;
    }

    public function getMutationScore(): ?float
    {
        return $this->mutationScore;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
