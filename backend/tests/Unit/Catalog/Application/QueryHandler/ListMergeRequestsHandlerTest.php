<?php

declare(strict_types=1);

use App\Catalog\Application\DTO\MergeRequestListOutput;
use App\Catalog\Application\DTO\MergeRequestOutput;
use App\Catalog\Application\Query\ListMergeRequestsQuery;
use App\Catalog\Application\QueryHandler\ListMergeRequestsHandler;
use App\Catalog\Domain\Model\MergeRequest;
use App\Catalog\Domain\Model\MergeRequestStatus;
use App\Catalog\Domain\Repository\MergeRequestRepositoryInterface;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\ProjectFactory;

function stubListMRRepo(array $items = [], int $count = 0): MergeRequestRepositoryInterface
{
    return new class ($items, $count) implements MergeRequestRepositoryInterface {
        public function __construct(
            private readonly array $items,
            private readonly int $count,
        ) {}
        public function findById(Uuid $id): ?MergeRequest { return null; }
        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20, array $statuses = [], ?string $author = null): array { return $this->items; }
        public function findByExternalIdAndProject(string $externalId, Uuid $projectId): ?MergeRequest { return null; }
        public function countByProjectId(Uuid $projectId, array $statuses = [], ?string $author = null): int { return $this->count; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function count(): int { return 0; }
        public function save(MergeRequest $mergeRequest): void {}
        public function delete(MergeRequest $mergeRequest): void {}
    };
}

describe('ListMergeRequestsHandler', function () {
    it('returns paginated merge requests for a project', function () {
        $project = ProjectFactory::create();
        $mr = MergeRequest::create(
            externalId: '42',
            title: 'feat: login',
            description: null,
            sourceBranch: 'feature/login',
            targetBranch: 'main',
            status: MergeRequestStatus::Open,
            author: 'dev',
            url: 'https://gitlab.com/test/-/merge_requests/42',
            additions: 100,
            deletions: 20,
            reviewers: ['alice'],
            labels: ['feature'],
            mergedAt: null,
            closedAt: null,
            project: $project,
        );

        $handler = new ListMergeRequestsHandler(stubListMRRepo([$mr], 1));
        $result = $handler(new ListMergeRequestsQuery($project->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(MergeRequestListOutput::class);
        expect($result->pagination->items)->toHaveCount(1);
        expect($result->pagination->total)->toBe(1);
        expect($result->pagination->items[0])->toBeInstanceOf(MergeRequestOutput::class);
        expect($result->pagination->items[0]->externalId)->toBe('42');
        expect($result->pagination->items[0]->status)->toBe('open');
        expect($result->pagination->items[0]->author)->toBe('dev');
    });

    it('returns empty list for project with no MRs', function () {
        $handler = new ListMergeRequestsHandler(stubListMRRepo([], 0));
        $result = $handler(new ListMergeRequestsQuery(Uuid::v7()->toRfc4122()));

        expect($result->pagination->items)->toBeEmpty();
        expect($result->pagination->total)->toBe(0);
    });
});
