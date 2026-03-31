<?php

declare(strict_types=1);

namespace App\Coverage\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'coverage_snapshots')]
#[ORM\Index(name: 'idx_coverage_project', columns: ['project_id'])]
#[ORM\Index(name: 'idx_coverage_project_commit', columns: ['project_id', 'commit_hash'])]
final class CoverageSnapshot
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'uuid')]
    private Uuid $projectId;

    #[ORM\Column(length: 40)]
    private string $commitHash;

    #[ORM\Column(type: 'float')]
    private float $coveragePercent;

    #[ORM\Column(type: 'string', enumType: CoverageSource::class)]
    private CoverageSource $source;

    #[ORM\Column(length: 255)]
    private string $ref;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pipelineId;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    private function __construct(
        Uuid $id,
        Uuid $projectId,
        string $commitHash,
        float $coveragePercent,
        CoverageSource $source,
        string $ref,
        ?string $pipelineId,
    ) {
        if (\trim($commitHash) === '') {
            throw new InvalidArgumentException('Commit hash must not be blank.');
        }
        if ($coveragePercent < 0.0 || $coveragePercent > 100.0) {
            throw new InvalidArgumentException('Coverage percent must be between 0 and 100.');
        }

        $this->id = $id;
        $this->projectId = $projectId;
        $this->commitHash = $commitHash;
        $this->coveragePercent = $coveragePercent;
        $this->source = $source;
        $this->ref = $ref;
        $this->pipelineId = $pipelineId;
        $this->createdAt = new DateTimeImmutable();
    }

    public static function create(
        Uuid $projectId,
        string $commitHash,
        float $coveragePercent,
        CoverageSource $source,
        string $ref,
        ?string $pipelineId = null,
    ): self {
        return new self(
            id: Uuid::v7(),
            projectId: $projectId,
            commitHash: $commitHash,
            coveragePercent: $coveragePercent,
            source: $source,
            ref: $ref,
            pipelineId: $pipelineId,
        );
    }

    public function getId(): Uuid { return $this->id; }
    public function getProjectId(): Uuid { return $this->projectId; }
    public function getCommitHash(): string { return $this->commitHash; }
    public function getCoveragePercent(): float { return $this->coveragePercent; }
    public function getSource(): CoverageSource { return $this->source; }
    public function getRef(): string { return $this->ref; }
    public function getPipelineId(): ?string { return $this->pipelineId; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
}
