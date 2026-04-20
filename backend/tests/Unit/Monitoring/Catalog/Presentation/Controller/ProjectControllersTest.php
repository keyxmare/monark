<?php

declare(strict_types=1);

use App\Hub\Shared\Application\DTO\PaginatedOutput;
use App\Monitoring\Catalog\Application\Command\CreateProjectCommand;
use App\Monitoring\Catalog\Application\Command\DeleteProjectCommand;
use App\Monitoring\Catalog\Application\Command\ScanProjectCommand;
use App\Monitoring\Catalog\Application\Command\UpdateProjectCommand;
use App\Monitoring\Catalog\Application\CommandHandler\ScanProjectHandler;
use App\Monitoring\Catalog\Application\DTO\CreateProjectInput;
use App\Monitoring\Catalog\Application\DTO\ProjectListOutput;
use App\Monitoring\Catalog\Application\DTO\ProjectOutput;
use App\Monitoring\Catalog\Application\DTO\ScanResultOutput;
use App\Monitoring\Catalog\Application\DTO\UpdateProjectInput;
use App\Monitoring\Catalog\Application\Query\GetProjectQuery;
use App\Monitoring\Catalog\Application\Query\ListProjectsQuery;
use App\Monitoring\Catalog\Presentation\Controller\CreateProjectController;
use App\Monitoring\Catalog\Presentation\Controller\DeleteProjectController;
use App\Monitoring\Catalog\Presentation\Controller\GetProjectController;
use App\Monitoring\Catalog\Presentation\Controller\ListProjectsController;
use App\Monitoring\Catalog\Presentation\Controller\ScanProjectController;
use App\Monitoring\Catalog\Presentation\Controller\UpdateProjectController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

function stubProjectBus(mixed $result = null): MessageBusInterface&stdClass
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

function makeProjectOutput(string $name = 'Monark', string $slug = 'monark'): ProjectOutput
{
    return new ProjectOutput(
        id: 'uuid-1',
        name: $name,
        slug: $slug,
        description: null,
        repositoryUrl: 'https://github.com/x/y',
        defaultBranch: 'main',
        visibility: 'private',
        ownerId: 'owner-1',
        providerId: null,
        externalId: null,
        createdAt: '2026-01-01T00:00:00+00:00',
        updatedAt: '2026-01-01T00:00:00+00:00',
        techStacks: [],
        techStacksCount: 0,
        runtimes: [],
        frameworkLag: \App\Monitoring\Catalog\Application\DTO\FrameworkLagSummary::empty(),
        coveragePercent: null,
        coverageJobs: [],
        dependenciesCount: 0,
        outdatedDependenciesCount: 0,
        vulnerabilitiesCount: 0,
        vulnerabilitiesBySeverity: ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0],
        lastActivityAt: null,
        lastCommitSha: null,
        commitsLast30d: 0,
        commitsDailySeries: \array_fill(0, 30, 0),
    );
}

it('creates a project and returns 201', function () {
    $output = \makeProjectOutput();
    $bus = \stubProjectBus($output);
    $controller = new CreateProjectController($bus);

    $input = new CreateProjectInput(name: 'Monark', slug: 'monark', repositoryUrl: 'https://github.com/x/y', ownerId: 'a0000000-0000-0000-0000-000000000001');
    $response = $controller($input);

    expect($response->getStatusCode())->toBe(201);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['success'])->toBeTrue();
    expect($data['data'])->toBeArray();
    expect($bus->dispatched)->toBeInstanceOf(CreateProjectCommand::class);
});

it('gets a project and returns 200', function () {
    $output = \makeProjectOutput();
    $bus = \stubProjectBus($output);
    $controller = new GetProjectController($bus);

    $response = $controller('uuid-1');

    expect($response->getStatusCode())->toBe(200);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['success'])->toBeTrue();
    expect($bus->dispatched)->toBeInstanceOf(GetProjectQuery::class);
    expect($bus->dispatched->projectId)->toBe('uuid-1');
});

it('updates a project and returns 200', function () {
    $output = \makeProjectOutput('Updated', 'updated');
    $bus = \stubProjectBus($output);
    $controller = new UpdateProjectController($bus);

    $input = new UpdateProjectInput(name: 'Updated');
    $response = $controller('uuid-1', $input);

    expect($response->getStatusCode())->toBe(200);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['success'])->toBeTrue();
    expect($bus->dispatched)->toBeInstanceOf(UpdateProjectCommand::class);
    expect($bus->dispatched->projectId)->toBe('uuid-1');
});

it('deletes a project and returns 204', function () {
    $bus = \stubProjectBus();
    $controller = new DeleteProjectController($bus);

    $response = $controller('uuid-1');

    expect($response->getStatusCode())->toBe(204);
    expect($bus->dispatched)->toBeInstanceOf(DeleteProjectCommand::class);
    expect($bus->dispatched->projectId)->toBe('uuid-1');
});

it('lists projects and returns paginated 200', function () {
    $listOutput = new ProjectListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20));
    $bus = \stubProjectBus($listOutput);
    $controller = new ListProjectsController($bus);

    $request = Request::create('/api/v1/monitoring/catalog/projects', 'GET', ['page' => 2, 'per_page' => 10]);
    $response = $controller($request);

    expect($response->getStatusCode())->toBe(200);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['success'])->toBeTrue();
    expect($data['data'])->toHaveKeys(['items', 'total', 'page', 'per_page']);
    expect($bus->dispatched)->toBeInstanceOf(ListProjectsQuery::class);
    expect($bus->dispatched->page)->toBe(2);
    expect($bus->dispatched->perPage)->toBe(10);
});

it('lists projects with default pagination', function () {
    $listOutput = new ProjectListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20));
    $bus = \stubProjectBus($listOutput);
    $controller = new ListProjectsController($bus);

    $request = Request::create('/api/v1/monitoring/catalog/projects', 'GET');
    $response = $controller($request);

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(ListProjectsQuery::class);
    expect($bus->dispatched->page)->toBe(1);
    expect($bus->dispatched->perPage)->toBe(20);
});

it('scans a project and returns 200 with results', function () {
    $scanResult = new ScanResultOutput(stacksDetected: 2, dependenciesDetected: 5, stacks: [], dependencies: []);

    $handler = $this->createMock(ScanProjectHandler::class);
    $handler->expects($this->once())
        ->method('__invoke')
        ->with($this->callback(fn (ScanProjectCommand $cmd) => $cmd->projectId === 'uuid-1'))
        ->willReturn($scanResult);

    $controller = new ScanProjectController($handler);

    $response = $controller('uuid-1');

    expect($response->getStatusCode())->toBe(200);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['success'])->toBeTrue();
    expect($data['data']['stacksDetected'])->toBe(2);
    expect($data['data']['dependenciesDetected'])->toBe(5);
});
