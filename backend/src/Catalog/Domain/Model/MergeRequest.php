<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Model;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'catalog_merge_requests')]
#[ORM\Index(name: 'idx_mr_project_status', columns: ['project_id', 'status'])]
final class MergeRequest
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $externalId;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\Column(length: 255)]
    private string $sourceBranch;

    #[ORM\Column(length: 255)]
    private string $targetBranch;

    #[ORM\Column(type: 'string', enumType: MergeRequestStatus::class)]
    private MergeRequestStatus $status;

    #[ORM\Column(length: 255)]
    private string $author;

    #[ORM\Column(length: 500)]
    private string $url;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $additions;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $deletions;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $reviewers;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $labels;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $mergedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $closedAt;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'mergeRequests')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /**
     * @param list<string> $reviewers
     * @param list<string> $labels
     */
    private function __construct(
        Uuid $id,
        string $externalId,
        string $title,
        ?string $description,
        string $sourceBranch,
        string $targetBranch,
        MergeRequestStatus $status,
        string $author,
        string $url,
        ?int $additions,
        ?int $deletions,
        array $reviewers,
        array $labels,
        ?\DateTimeImmutable $mergedAt,
        ?\DateTimeImmutable $closedAt,
        Project $project,
    ) {
        $this->id = $id;
        $this->externalId = $externalId;
        $this->title = $title;
        $this->description = $description;
        $this->sourceBranch = $sourceBranch;
        $this->targetBranch = $targetBranch;
        $this->status = $status;
        $this->author = $author;
        $this->url = $url;
        $this->additions = $additions;
        $this->deletions = $deletions;
        $this->reviewers = $reviewers;
        $this->labels = $labels;
        $this->mergedAt = $mergedAt;
        $this->closedAt = $closedAt;
        $this->project = $project;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * @param list<string> $reviewers
     * @param list<string> $labels
     */
    public static function create(
        string $externalId,
        string $title,
        ?string $description,
        string $sourceBranch,
        string $targetBranch,
        MergeRequestStatus $status,
        string $author,
        string $url,
        ?int $additions,
        ?int $deletions,
        array $reviewers,
        array $labels,
        ?\DateTimeImmutable $mergedAt,
        ?\DateTimeImmutable $closedAt,
        Project $project,
    ): self {
        return new self(
            id: Uuid::v7(),
            externalId: $externalId,
            title: $title,
            description: $description,
            sourceBranch: $sourceBranch,
            targetBranch: $targetBranch,
            status: $status,
            author: $author,
            url: $url,
            additions: $additions,
            deletions: $deletions,
            reviewers: $reviewers,
            labels: $labels,
            mergedAt: $mergedAt,
            closedAt: $closedAt,
            project: $project,
        );
    }

    public function update(
        ?string $title = null,
        ?string $description = null,
        ?MergeRequestStatus $status = null,
        ?int $additions = null,
        ?int $deletions = null,
        ?array $reviewers = null,
        ?array $labels = null,
        ?\DateTimeImmutable $mergedAt = null,
        ?\DateTimeImmutable $closedAt = null,
    ): void {
        if ($title !== null) {
            $this->title = $title;
        }
        if ($description !== null) {
            $this->description = $description;
        }
        if ($status !== null) {
            $this->status = $status;
        }
        if ($additions !== null) {
            $this->additions = $additions;
        }
        if ($deletions !== null) {
            $this->deletions = $deletions;
        }
        if ($reviewers !== null) {
            $this->reviewers = $reviewers;
        }
        if ($labels !== null) {
            $this->labels = $labels;
        }
        if ($mergedAt !== null) {
            $this->mergedAt = $mergedAt;
        }
        if ($closedAt !== null) {
            $this->closedAt = $closedAt;
        }
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getSourceBranch(): string
    {
        return $this->sourceBranch;
    }

    public function getTargetBranch(): string
    {
        return $this->targetBranch;
    }

    public function getStatus(): MergeRequestStatus
    {
        return $this->status;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getAdditions(): ?int
    {
        return $this->additions;
    }

    public function getDeletions(): ?int
    {
        return $this->deletions;
    }

    /** @return list<string> */
    public function getReviewers(): array
    {
        return $this->reviewers;
    }

    /** @return list<string> */
    public function getLabels(): array
    {
        return $this->labels;
    }

    public function getMergedAt(): ?\DateTimeImmutable
    {
        return $this->mergedAt;
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
