<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\SyncJob;
use App\Catalog\Domain\Repository\SyncJobRepositoryInterface;
use App\Catalog\Presentation\Controller\GetSyncJobController;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;

function stubSyncJobControllerRepo(?SyncJob $result = null): SyncJobRepositoryInterface
{
    return new class ($result) implements SyncJobRepositoryInterface {
        public function __construct(private readonly ?SyncJob $result)
        {
        }

        public function findById(Uuid $id): ?SyncJob
        {
            return $this->result;
        }

        public function save(SyncJob $syncJob): void
        {
        }
    };
}

it('returns sync job details when found', function () {
    $syncJob = SyncJob::create(5);
    $repo = \stubSyncJobControllerRepo($syncJob);
    $controller = new GetSyncJobController($repo);

    $response = $controller($syncJob->getId()->toRfc4122());

    expect($response->getStatusCode())->toBe(200);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['success'])->toBeTrue();
    expect($data['data']['totalProjects'])->toBe(5);
    expect($data['data']['completedProjects'])->toBe(0);
    expect($data['data']['status'])->toBe('running');
});

it('throws not found when sync job does not exist', function () {
    $repo = \stubSyncJobControllerRepo(null);
    $controller = new GetSyncJobController($repo);

    $controller(Uuid::v7()->toRfc4122());
})->throws(NotFoundException::class);
