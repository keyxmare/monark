<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

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
}
