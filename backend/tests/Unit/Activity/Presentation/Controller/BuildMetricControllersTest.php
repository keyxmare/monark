<?php

declare(strict_types=1);

use App\Activity\Application\Command\CreateBuildMetricCommand;
use App\Activity\Application\DTO\BuildMetricListOutput;
use App\Activity\Application\DTO\BuildMetricOutput;
use App\Activity\Application\DTO\CreateBuildMetricInput;
use App\Activity\Application\Query\GetLatestBuildMetricQuery;
use App\Activity\Application\Query\ListBuildMetricsQuery;
use App\Activity\Presentation\Controller\CreateBuildMetricController;
use App\Activity\Presentation\Controller\GetLatestBuildMetricController;
use App\Activity\Presentation\Controller\ListBuildMetricsController;
use App\Shared\Application\DTO\PaginatedOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

function stubBuildMetricBus(mixed $result = null): MessageBusInterface&stdClass
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

it('creates a build metric and returns 201', function () {
    $output = new BuildMetricOutput('bm-1', 'proj-1', 'abc123', 'master', 82.6, 16.37, 76.55, '2026-01-01T00:00:00+00:00');
    $bus = \stubBuildMetricBus($output);
    $controller = new CreateBuildMetricController($bus);

    $input = new CreateBuildMetricInput(commitSha: 'abc123', ref: 'master', backendCoverage: 82.6);
    $response = $controller('proj-1', $input);

    expect($response->getStatusCode())->toBe(201);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['success'])->toBeTrue();
    expect($bus->dispatched)->toBeInstanceOf(CreateBuildMetricCommand::class);
    expect($bus->dispatched->projectId)->toBe('proj-1');
});

it('lists build metrics with pagination', function () {
    $listOutput = new BuildMetricListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20));
    $bus = \stubBuildMetricBus($listOutput);
    $controller = new ListBuildMetricsController($bus);

    $request = Request::create('/api/v1/activity/projects/proj-1/build-metrics', 'GET', ['page' => 2, 'per_page' => 10]);
    $response = $controller('proj-1', $request);

    expect($response->getStatusCode())->toBe(200);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['data'])->toHaveKeys(['items', 'total', 'page', 'per_page']);
    expect($bus->dispatched)->toBeInstanceOf(ListBuildMetricsQuery::class);
    expect($bus->dispatched->page)->toBe(2);
    expect($bus->dispatched->perPage)->toBe(10);
});

it('lists build metrics with default pagination', function () {
    $listOutput = new BuildMetricListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20));
    $bus = \stubBuildMetricBus($listOutput);
    $controller = new ListBuildMetricsController($bus);

    $request = Request::create('/api/v1/activity/projects/proj-1/build-metrics', 'GET');
    $response = $controller('proj-1', $request);

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(ListBuildMetricsQuery::class);
    expect($bus->dispatched->page)->toBe(1);
    expect($bus->dispatched->perPage)->toBe(20);
});

it('gets latest build metric', function () {
    $output = new BuildMetricOutput('bm-1', 'proj-1', 'abc123', 'master', 82.6, null, null, '2026-01-01T00:00:00+00:00');
    $bus = \stubBuildMetricBus($output);
    $controller = new GetLatestBuildMetricController($bus);

    $response = $controller('proj-1');

    expect($response->getStatusCode())->toBe(200);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['success'])->toBeTrue();
    expect($bus->dispatched)->toBeInstanceOf(GetLatestBuildMetricQuery::class);
    expect($bus->dispatched->projectId)->toBe('proj-1');
});

it('returns null when no build metrics exist', function () {
    $bus = \stubBuildMetricBus(null);
    $controller = new GetLatestBuildMetricController($bus);

    $response = $controller('proj-1');

    expect($response->getStatusCode())->toBe(200);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['data'])->toBeNull();
});
