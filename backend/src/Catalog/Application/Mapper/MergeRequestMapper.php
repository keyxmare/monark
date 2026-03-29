<?php

declare(strict_types=1);

namespace App\Catalog\Application\Mapper;

use App\Catalog\Application\DTO\MergeRequestOutput;
use App\Catalog\Domain\Model\MergeRequest;
use DateTimeInterface;

final class MergeRequestMapper
{
    public static function toOutput(MergeRequest $mr): MergeRequestOutput
    {
        return new MergeRequestOutput(
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
