<?php

declare(strict_types=1);

use App\Activity\Application\Command\UpdateSyncTaskStatusCommand;
use App\Activity\Application\DTO\MessengerStatsOutput;
use App\Activity\Application\DTO\SyncTaskListOutput;
use App\Activity\Application\DTO\SyncTaskStatsOutput;
use App\Activity\Application\Query\GetMessengerStatsQuery;
use App\Activity\Application\Query\GetSyncTaskQuery;
use App\Activity\Application\Query\GetSyncTaskStatsQuery;
use App\Activity\Application\Query\ListSyncTasksQuery;
use App\Activity\Presentation\Controller\GetMessengerStatsController;
use App\Activity\Presentation\Controller\GetSyncTaskStatsController;
use App\Activity\Presentation\Controller\ListSyncTasksController;
use App\Activity\Presentation\Controller\UpdateSyncTaskStatusController;
use App\Shared\Application\DTO\PaginatedOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

function stubActivityBus(mixed $result = null): MessageBusInterface&stdClass
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

it('gets a sync task by id', function () {
    $bus = \stubActivityBus(['id' => 'st-1', 'title' => 'Update PHP']);
    $controller = new \App\Activity\Presentation\Controller\GetSyncTaskController($bus);

    $response = $controller('st-1');

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(GetSyncTaskQuery::class);
});

it('lists sync tasks with filters', function () {
    $listOutput = new SyncTaskListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20));
    $bus = \stubActivityBus($listOutput);
    $controller = new ListSyncTasksController($bus);

    $request = Request::create('/api/activity/sync-tasks', 'GET', [
        'status' => 'open',
        'type' => 'vulnerability',
        'severity' => 'critical',
    ]);
    $response = $controller($request);

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(ListSyncTasksQuery::class);
    expect($bus->dispatched->page)->toBe(1);
    expect($bus->dispatched->perPage)->toBe(20);
    expect($bus->dispatched->status)->toBe('open');
    expect($bus->dispatched->type)->toBe('vulnerability');
    expect($bus->dispatched->severity)->toBe('critical');
});

it('updates sync task status via PATCH', function () {
    $bus = \stubActivityBus(['id' => 'st-1', 'status' => 'resolved']);
    $controller = new UpdateSyncTaskStatusController($bus);

    $request = Request::create('/api/activity/sync-tasks/st-1', 'PATCH', [], [], [], [], \json_encode(['status' => 'resolved']));
    $response = $controller('st-1', $request);

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(UpdateSyncTaskStatusCommand::class);
});

it('gets sync task stats', function () {
    $stats = new SyncTaskStatsOutput(
        byType: [['label' => 'vulnerability', 'count' => 3]],
        bySeverity: [['label' => 'critical', 'count' => 1]],
        byStatus: [['label' => 'open', 'count' => 2]],
    );
    $bus = \stubActivityBus($stats);
    $controller = new GetSyncTaskStatsController($bus);

    $response = $controller();

    expect($response->getStatusCode())->toBe(200);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['success'])->toBeTrue();
    expect($data['data'])->toHaveKeys(['by_type', 'by_severity', 'by_status']);
    expect($bus->dispatched)->toBeInstanceOf(GetSyncTaskStatsQuery::class);
});

it('gets messenger stats', function () {
    $stats = new MessengerStatsOutput(queues: [], workers: []);
    $bus = \stubActivityBus($stats);
    $controller = new GetMessengerStatsController($bus);

    $response = $controller();

    expect($response->getStatusCode())->toBe(200);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['data'])->toHaveKeys(['queues', 'workers']);
    expect($bus->dispatched)->toBeInstanceOf(GetMessengerStatsQuery::class);
});
