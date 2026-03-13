<?php

declare(strict_types=1);

use App\Catalog\Application\Command\CreateProjectCommand;
use App\Catalog\Application\Command\DeleteProjectCommand;
use App\Catalog\Application\Command\ScanProjectCommand;
use App\Catalog\Application\Command\UpdateProjectCommand;
use App\Catalog\Application\DTO\CreateProjectInput;
use App\Catalog\Application\DTO\ProjectListOutput;
use App\Catalog\Application\DTO\ProjectOutput;
use App\Catalog\Application\DTO\UpdateProjectInput;
use App\Catalog\Application\Query\GetProjectQuery;
use App\Catalog\Application\Query\ListProjectsQuery;
use App\Catalog\Presentation\Controller\CreateProjectController;
use App\Catalog\Presentation\Controller\DeleteProjectController;
use App\Catalog\Presentation\Controller\GetProjectController;
use App\Catalog\Presentation\Controller\ListProjectsController;
use App\Catalog\Presentation\Controller\ScanProjectController;
use App\Catalog\Presentation\Controller\UpdateProjectController;
use App\Shared\Application\DTO\PaginatedOutput;
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

it('creates a project and returns 201', function () {
    $output = new ProjectOutput('uuid-1', 'Monark', 'monark', null, 'https://github.com/x/y', 'main', 'private', 'owner-1', null, null, 0, 0, '2026-01-01T00:00:00+00:00', '2026-01-01T00:00:00+00:00');
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
    $output = new ProjectOutput('uuid-1', 'Monark', 'monark', null, 'https://github.com/x/y', 'main', 'private', 'owner-1', null, null, 0, 0, '2026-01-01T00:00:00+00:00', '2026-01-01T00:00:00+00:00');
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
    $output = new ProjectOutput('uuid-1', 'Updated', 'updated', null, 'https://github.com/x/y', 'main', 'private', 'owner-1', null, null, 0, 0, '2026-01-01T00:00:00+00:00', '2026-01-01T00:00:00+00:00');
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

    $request = Request::create('/api/catalog/projects', 'GET', ['page' => 2, 'per_page' => 10]);
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

    $request = Request::create('/api/catalog/projects', 'GET');
    $response = $controller($request);

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(ListProjectsQuery::class);
    expect($bus->dispatched->page)->toBe(1);
    expect($bus->dispatched->perPage)->toBe(20);
});

it('scans a project and returns 202', function () {
    $bus = \stubProjectBus();
    $controller = new ScanProjectController($bus);

    $response = $controller('uuid-1');

    expect($response->getStatusCode())->toBe(202);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['success'])->toBeTrue();
    expect($data['data']['message'])->toBe('Scan started');
    expect($bus->dispatched)->toBeInstanceOf(ScanProjectCommand::class);
    expect($bus->dispatched->projectId)->toBe('uuid-1');
});
