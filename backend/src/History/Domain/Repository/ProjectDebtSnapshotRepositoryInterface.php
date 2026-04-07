<?php

declare(strict_types=1);

namespace App\History\Domain\Repository;

use App\History\Domain\Model\ProjectDebtSnapshot;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

interface ProjectDebtSnapshotRepositoryInterface
{
    public function findById(Uuid $id): ?ProjectDebtSnapshot;

    public function findByProjectAndCommit(Uuid $projectId, string $commitSha): ?ProjectDebtSnapshot;

    /** @return list<ProjectDebtSnapshot> */
    public function findByProjectBetween(Uuid $projectId, ?DateTimeImmutable $from, ?DateTimeImmutable $to): array;

    public function save(ProjectDebtSnapshot $snapshot): void;
}
