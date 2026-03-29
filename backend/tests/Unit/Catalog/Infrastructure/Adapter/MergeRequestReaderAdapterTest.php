<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\MergeRequest;
use App\Catalog\Domain\Model\MergeRequestStatus;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Repository\MergeRequestRepositoryInterface;
use App\Catalog\Infrastructure\Adapter\MergeRequestReaderAdapter;
use App\Shared\Domain\DTO\MergeRequestReadDTO;
use Symfony\Component\Uid\Uuid;

function stubMrReaderRepo(array $mergeRequests = []): MergeRequestRepositoryInterface
{
    return new class ($mergeRequests) implements MergeRequestRepositoryInterface {
        public function __construct(private readonly array $mergeRequests)
        {
        }
        public function findById(Uuid $id): ?MergeRequest
        {
            return null;
        }
        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20, array $statuses = [], ?string $author = null): array
        {
            return $this->mergeRequests;
        }
        public function findByExternalIdAndProject(string $externalId, Uuid $projectId): ?MergeRequest
        {
            return null;
        }
        public function countByProjectId(Uuid $projectId, array $statuses = [], ?string $author = null): int
        {
            return 0;
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function count(): int
        {
            return 0;
        }
        public function save(MergeRequest $mergeRequest): void
        {
        }
        public function delete(MergeRequest $mergeRequest): void
        {
        }
    };
}

describe('MergeRequestReaderAdapter', function () {
    it('returns empty array when no merge requests exist', function () {
        $adapter = new MergeRequestReaderAdapter(\stubMrReaderRepo([]));
        $result = $adapter->findActiveByProjectId(Uuid::v7());

        expect($result)->toBeEmpty();
    });

    it('maps merge requests to DTOs', function () {
        $project = Project::create(
            name: 'Test Project',
            slug: 'test-project',
            description: null,
            repositoryUrl: 'https://github.com/test/project',
            defaultBranch: 'main',
            visibility: ProjectVisibility::Private,
            ownerId: Uuid::v7(),
        );

        $mr = MergeRequest::create(
            externalId: '42',
            title: 'Fix critical bug',
            description: 'Fixes the issue',
            sourceBranch: 'fix/critical',
            targetBranch: 'main',
            status: MergeRequestStatus::Open,
            author: 'dev-user',
            url: 'https://github.com/test/project/pull/42',
            additions: 10,
            deletions: 5,
            reviewers: [],
            labels: [],
            mergedAt: null,
            closedAt: null,
            project: $project,
        );

        $adapter = new MergeRequestReaderAdapter(\stubMrReaderRepo([$mr]));
        $result = $adapter->findActiveByProjectId(Uuid::v7());

        expect($result)->toHaveCount(1);
        expect($result[0])->toBeInstanceOf(MergeRequestReadDTO::class);
        expect($result[0]->externalId)->toBe('42');
        expect($result[0]->title)->toBe('Fix critical bug');
        expect($result[0]->author)->toBe('dev-user');
        expect($result[0]->status)->toBe('open');
        expect($result[0]->url)->toBe('https://github.com/test/project/pull/42');
        expect($result[0]->updatedAt)->toBeInstanceOf(DateTimeImmutable::class);
    });
});
