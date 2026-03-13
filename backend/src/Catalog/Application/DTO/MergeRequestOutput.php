<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

use App\Catalog\Domain\Model\MergeRequest;
use DateTimeInterface;

final readonly class MergeRequestOutput
{
    /**
     * @param list<string> $reviewers
     * @param list<string> $labels
     */
    public function __construct(
        public string $id,
        public string $externalId,
        public string $title,
        public ?string $description,
        public string $sourceBranch,
        public string $targetBranch,
        public string $status,
        public string $author,
        public string $url,
        public ?int $additions,
        public ?int $deletions,
        public array $reviewers,
        public array $labels,
        public ?string $mergedAt,
        public ?string $closedAt,
        public string $projectId,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(MergeRequest $mr): self
    {
        return new self(
            id: $mr->getId()->toRfc4122(),
            externalId: $mr->getExternalId(),
            title: $mr->getTitle(),
            description: $mr->getDescription(),
            sourceBranch: $mr->getSourceBranch(),
            targetBranch: $mr->getTargetBranch(),
            status: $mr->getStatus()->value,
            author: $mr->getAuthor(),
            url: $mr->getUrl(),
            additions: $mr->getAdditions(),
            deletions: $mr->getDeletions(),
            reviewers: $mr->getReviewers(),
            labels: $mr->getLabels(),
            mergedAt: $mr->getMergedAt()?->format(DateTimeInterface::ATOM),
            closedAt: $mr->getClosedAt()?->format(DateTimeInterface::ATOM),
            projectId: $mr->getProject()->getId()->toRfc4122(),
            createdAt: $mr->getCreatedAt()->format(DateTimeInterface::ATOM),
            updatedAt: $mr->getUpdatedAt()->format(DateTimeInterface::ATOM),
        );
    }
}
