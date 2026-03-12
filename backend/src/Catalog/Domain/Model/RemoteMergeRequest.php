<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Model;

final readonly class RemoteMergeRequest
{
    /**
     * @param list<string> $reviewers
     * @param list<string> $labels
     */
    public function __construct(
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
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?string $mergedAt,
        public ?string $closedAt,
    ) {
    }
}
