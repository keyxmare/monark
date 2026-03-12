<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Model\MergeRequest;
use App\Catalog\Domain\Model\MergeRequestStatus;
use Symfony\Component\Uid\Uuid;

interface MergeRequestRepositoryInterface
{
    public function findById(Uuid $id): ?MergeRequest;

    /**
     * @param list<MergeRequestStatus> $statuses
     * @return list<MergeRequest>
     */
    public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20, array $statuses = [], ?string $author = null): array;

    public function findByExternalIdAndProject(string $externalId, Uuid $projectId): ?MergeRequest;

    /** @param list<MergeRequestStatus> $statuses */
    public function countByProjectId(Uuid $projectId, array $statuses = [], ?string $author = null): int;

    /** @return list<MergeRequest> */
    public function findAll(int $page = 1, int $perPage = 20): array;

    public function count(): int;

    public function save(MergeRequest $mergeRequest): void;

    public function delete(MergeRequest $mergeRequest): void;
}
