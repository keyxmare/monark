<?php

declare(strict_types=1);

use App\Catalog\Application\Command\SyncAllProjectsCommand;
use App\Catalog\Domain\Model\SyncJob;
use App\Catalog\Domain\Repository\SyncJobRepositoryInterface;
use App\Catalog\Presentation\Controller\GetSyncJobController;
use App\Catalog\Presentation\Controller\SyncAllProjectsController;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Uid\Uuid;

function stubSyncControllersRepo(?SyncJob $result = null): SyncJobRepositoryInterface
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

function stubSyncControllersBus(mixed $result = null): MessageBusInterface&stdClass
{
    return new class ($result) extends stdClass implements MessageBusInterface {
        public ?object $dispatched = null;

        public function __construct(private readonly mixed $result)
        {
        }

        public function dispatch(object $message, array $stamps = []): Envelope
        {
            $this->dispatched = $message;
            $envelope = new Envelope($message);
            if ($this->result !== null) {
                $envelope = $envelope->with(new HandledStamp($this->result, 'handler'));
            }

            return $envelope;
        }
    };
}

describe('GetSyncJobController', function () {
    it('returns sync job data', function () {
        $syncJob = SyncJob::create(10);
        $repo = \stubSyncControllersRepo($syncJob);
        $controller = new GetSyncJobController($repo);

        $response = $controller($syncJob->getId()->toRfc4122());

        expect($response->getStatusCode())->toBe(200);
        $data = \json_decode((string) $response->getContent(), true);
        expect($data['success'])->toBeTrue();
        expect($data['data']['totalProjects'])->toBe(10);
        expect($data['data']['completedProjects'])->toBe(0);
        expect($data['data']['status'])->toBe('running');
    });

    it('throws NotFoundException for unknown id', function () {
        $repo = \stubSyncControllersRepo(null);
        $controller = new GetSyncJobController($repo);

        $controller(Uuid::v7()->toRfc4122());
    })->throws(NotFoundException::class);
});

describe('SyncAllProjectsController', function () {
    it('dispatches SyncAllProjectsCommand with force', function () {
        $bus = \stubSyncControllersBus(['syncJobId' => 'abc-123']);
        $controller = new SyncAllProjectsController($bus);

        $request = new Request(query: ['force' => '1']);
        $response = $controller->syncAll($request);

        expect($response->getStatusCode())->toBe(202);
        expect($bus->dispatched)->toBeInstanceOf(SyncAllProjectsCommand::class);
        expect($bus->dispatched->force)->toBeTrue();
        expect($bus->dispatched->providerId)->toBeNull();
        $data = \json_decode((string) $response->getContent(), true);
        expect($data['success'])->toBeTrue();
        expect($data['data']['syncJobId'])->toBe('abc-123');
    });

    it('syncByProvider dispatches with providerId', function () {
        $bus = \stubSyncControllersBus(['syncJobId' => 'xyz-789']);
        $controller = new SyncAllProjectsController($bus);

        $request = Request::create('/api/catalog/providers/prov-1/sync-all', 'POST', [], [], [], [], \json_encode(['projectIds' => ['p1', 'p2']]));
        $request->headers->set('Content-Type', 'application/json');
        $response = $controller->syncByProvider('prov-1', $request);

        expect($response->getStatusCode())->toBe(202);
        expect($bus->dispatched)->toBeInstanceOf(SyncAllProjectsCommand::class);
        expect($bus->dispatched->providerId)->toBe('prov-1');
        expect($bus->dispatched->projectIds)->toBe(['p1', 'p2']);
        expect($bus->dispatched->force)->toBeFalse();
    });
});
