<?php

declare(strict_types=1);

use App\Sync\Application\Command\GlobalSyncCommand;
use App\Sync\Domain\Model\GlobalSyncJob;
use App\Sync\Domain\Repository\GlobalSyncJobRepositoryInterface;
use App\Sync\Presentation\Controller\GetCurrentGlobalSyncController;
use App\Sync\Presentation\Controller\StartGlobalSyncController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

function stubGlobalSyncControllersRepo(?GlobalSyncJob $running): GlobalSyncJobRepositoryInterface
{
    return new class ($running) implements GlobalSyncJobRepositoryInterface {
        public int $saveCount = 0;

        public function __construct(private ?GlobalSyncJob $running)
        {
        }

        public function save(GlobalSyncJob $job): void
        {
            ++$this->saveCount;
        }

        public function findById(Uuid $id): ?GlobalSyncJob
        {
            return null;
        }

        public function findRunning(): ?GlobalSyncJob
        {
            return $this->running;
        }

        public function incrementProgressAtomic(Uuid $jobId): array
        {
            return ['progress' => 0, 'total' => 0];
        }

        public function findByIdForUpdate(Uuid $id): ?GlobalSyncJob
        {
            return null;
        }
    };
}

function stubGlobalSyncControllersBus(): MessageBusInterface
{
    return new class () implements MessageBusInterface {
        public ?object $dispatched = null;

        public function dispatch(object $message, array $stamps = []): Envelope
        {
            $this->dispatched = $message;

            return new Envelope($message);
        }
    };
}

describe('GetCurrentGlobalSyncController', function (): void {
    it('returns null data when no running job', function (): void {
        $controller = new GetCurrentGlobalSyncController(\stubGlobalSyncControllersRepo(null));

        $response = $controller();

        expect($response->getStatusCode())->toBe(200);
        $payload = \json_decode((string) $response->getContent(), true);
        expect($payload['success'])->toBeTrue();
        expect($payload['data'])->toBeNull();
    });

    it('returns job details when a job is running', function (): void {
        $job = GlobalSyncJob::create();
        $controller = new GetCurrentGlobalSyncController(\stubGlobalSyncControllersRepo($job));

        $response = $controller();

        expect($response->getStatusCode())->toBe(200);
        $payload = \json_decode((string) $response->getContent(), true);
        expect($payload['data']['syncId'])->toBe($job->getId()->toRfc4122());
        expect($payload['data']['status'])->toBe($job->getStatus()->value);
        expect($payload['data'])->toHaveKeys([
            'currentStep',
            'currentStepName',
            'stepProgress',
            'stepTotal',
            'completedSteps',
            'createdAt',
        ]);
    });
});

describe('StartGlobalSyncController', function (): void {
    it('returns 409 when a sync is already running', function (): void {
        $running = GlobalSyncJob::create();
        $repo = \stubGlobalSyncControllersRepo($running);
        $bus = \stubGlobalSyncControllersBus();
        $controller = new StartGlobalSyncController($repo, $bus);

        $response = $controller(new Request());

        expect($response->getStatusCode())->toBe(409);
        $payload = \json_decode((string) $response->getContent(), true);
        expect($payload['success'])->toBeFalse();
        expect($bus->dispatched)->toBeNull();
        expect($repo->saveCount)->toBe(0);
    });

    it('creates a new job, saves it, dispatches GlobalSyncCommand and returns 202', function (): void {
        $repo = \stubGlobalSyncControllersRepo(null);
        $bus = \stubGlobalSyncControllersBus();
        $controller = new StartGlobalSyncController($repo, $bus);

        $response = $controller(new Request(content: '{}'));

        expect($response->getStatusCode())->toBe(202);
        $payload = \json_decode((string) $response->getContent(), true);
        expect($payload['data']['syncId'])->toBeString();
        expect($payload['data']['status'])->toBe('running');
        expect($repo->saveCount)->toBe(1);
        expect($bus->dispatched)->toBeInstanceOf(GlobalSyncCommand::class);
    });

    it('accepts an optional projectId in the request body', function (): void {
        $repo = \stubGlobalSyncControllersRepo(null);
        $bus = \stubGlobalSyncControllersBus();
        $controller = new StartGlobalSyncController($repo, $bus);
        $projectId = Uuid::v7()->toRfc4122();

        $response = $controller(new Request(content: \json_encode(['projectId' => $projectId])));

        expect($response->getStatusCode())->toBe(202);
        expect($bus->dispatched)->toBeInstanceOf(GlobalSyncCommand::class);
        /** @var GlobalSyncCommand $cmd */
        $cmd = $bus->dispatched;
        expect($cmd->projectId)->toBe($projectId);
    });
});
