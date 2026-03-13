<?php

declare(strict_types=1);

use App\Catalog\Application\DTO\MergeRequestOutput;
use App\Catalog\Application\Query\GetMergeRequestQuery;
use App\Catalog\Application\QueryHandler\GetMergeRequestHandler;
use App\Catalog\Domain\Model\MergeRequest;
use App\Catalog\Domain\Model\MergeRequestStatus;
use App\Catalog\Domain\Repository\MergeRequestRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\ProjectFactory;

function stubGetMRRepo(?MergeRequest $mr = null): MergeRequestRepositoryInterface
{
    return new class ($mr) implements MergeRequestRepositoryInterface {
        public function __construct(private readonly ?MergeRequest $mr)
        {
        }
        public function findById(Uuid $id): ?MergeRequest
        {
            return $this->mr;
        }
        public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20, array $statuses = [], ?string $author = null): array
        {
            return [];
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

describe('GetMergeRequestHandler', function () {
    it('returns a merge request by id', function () {
        $project = ProjectFactory::create();
        $mr = MergeRequest::create(
            externalId: '42',
            title: 'feat: login',
            description: 'Login page',
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

        $handler = new GetMergeRequestHandler(\stubGetMRRepo($mr));
        $result = $handler(new GetMergeRequestQuery($mr->getId()->toRfc4122()));

        expect($result)->toBeInstanceOf(MergeRequestOutput::class);
        expect($result->externalId)->toBe('42');
        expect($result->title)->toBe('feat: login');
        expect($result->description)->toBe('Login page');
        expect($result->status)->toBe('open');
        expect($result->reviewers)->toBe(['alice']);
        expect($result->labels)->toBe(['feature']);
    });

    it('throws not found when MR does not exist', function () {
        $handler = new GetMergeRequestHandler(\stubGetMRRepo(null));
        $handler(new GetMergeRequestQuery('00000000-0000-0000-0000-000000000000'));
    })->throws(NotFoundException::class);
});
