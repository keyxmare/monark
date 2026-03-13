<?php

declare(strict_types=1);

namespace Tests\Factory\Catalog;

use App\Catalog\Domain\Model\Pipeline;
use App\Catalog\Domain\Model\PipelineStatus;
use App\Catalog\Domain\Model\Project;
use DateTimeImmutable;

final class PipelineFactory
{
    public static function create(
        string $externalId = '12345',
        string $ref = 'main',
        PipelineStatus $status = PipelineStatus::Success,
        int $duration = 120,
        ?DateTimeImmutable $startedAt = null,
        ?DateTimeImmutable $finishedAt = null,
        ?Project $project = null,
    ): Pipeline {
        return Pipeline::create(
            externalId: $externalId,
            ref: $ref,
            status: $status,
            duration: $duration,
            startedAt: $startedAt ?? new DateTimeImmutable(),
            finishedAt: $finishedAt ?? new DateTimeImmutable(),
            project: $project ?? ProjectFactory::create(),
        );
    }
}
