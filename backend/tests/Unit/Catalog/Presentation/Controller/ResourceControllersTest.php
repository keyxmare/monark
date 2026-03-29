<?php

declare(strict_types=1);

use App\Catalog\Application\Command\CreateTechStackCommand;
use App\Catalog\Application\Command\DeleteTechStackCommand;
use App\Catalog\Application\DTO\CreateTechStackInput;
use App\Catalog\Application\DTO\MergeRequestListOutput;
use App\Catalog\Application\DTO\TechStackListOutput;
use App\Catalog\Application\DTO\TechStackOutput;
use App\Catalog\Application\Query\GetMergeRequestQuery;
use App\Catalog\Application\Query\GetTechStackQuery;
use App\Catalog\Application\Query\ListMergeRequestsQuery;
use App\Catalog\Application\Query\ListTechStacksQuery;
use App\Catalog\Presentation\Controller\CreateTechStackController;
use App\Catalog\Presentation\Controller\DeleteTechStackController;
use App\Catalog\Presentation\Controller\GetMergeRequestController;
use App\Catalog\Presentation\Controller\GetTechStackController;
use App\Catalog\Presentation\Controller\ListMergeRequestsController;
use App\Catalog\Presentation\Controller\ListTechStacksController;
use App\Shared\Application\DTO\PaginatedOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

function stubResourceBus(mixed $result = null): MessageBusInterface&stdClass
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

it('creates a tech stack and returns 201', function () {
    $output = new TechStackOutput('ts-1', 'PHP', 'Symfony', '8.0', '', '2026-01-01T00:00:00+00:00', 'proj-1', '2026-01-01T00:00:00+00:00');
    $bus = \stubResourceBus($output);
    $controller = new CreateTechStackController($bus);

    $input = new CreateTechStackInput(
        language: 'PHP',
        framework: 'Symfony',
        version: '8.0',
        detectedAt: '2026-01-01T00:00:00+00:00',
        projectId: 'a0000000-0000-0000-0000-000000000001',
    );
    $response = $controller($input);

    expect($response->getStatusCode())->toBe(201);
    expect($bus->dispatched)->toBeInstanceOf(CreateTechStackCommand::class);
});

it('gets a tech stack and returns 200', function () {
    $output = new TechStackOutput('ts-1', 'PHP', 'Symfony', '8.0', '', '2026-01-01T00:00:00+00:00', 'proj-1', '2026-01-01T00:00:00+00:00');
    $bus = \stubResourceBus($output);
    $controller = new GetTechStackController($bus);

    $response = $controller('ts-1');

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(GetTechStackQuery::class);
});

it('deletes a tech stack and returns 204', function () {
    $bus = \stubResourceBus();
    $controller = new DeleteTechStackController($bus);

    $response = $controller('ts-1');

    expect($response->getStatusCode())->toBe(204);
    expect($bus->dispatched)->toBeInstanceOf(DeleteTechStackCommand::class);
});

it('lists tech stacks with project filter', function () {
    $listOutput = new TechStackListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20));
    $bus = \stubResourceBus($listOutput);
    $controller = new ListTechStacksController($bus);

    $request = Request::create('/api/v1/catalog/tech-stacks', 'GET', ['project_id' => 'proj-1']);
    $response = $controller($request);

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(ListTechStacksQuery::class);
    expect($bus->dispatched->projectId)->toBe('proj-1');
    expect($bus->dispatched->page)->toBe(1);
    expect($bus->dispatched->perPage)->toBe(20);
});

it('gets a merge request and returns 200', function () {
    $bus = \stubResourceBus(['id' => 'mr-1', 'title' => 'Fix bug']);
    $controller = new GetMergeRequestController($bus);

    $response = $controller('mr-1');

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(GetMergeRequestQuery::class);
});

it('lists merge requests with filters', function () {
    $listOutput = new MergeRequestListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20));
    $bus = \stubResourceBus($listOutput);
    $controller = new ListMergeRequestsController($bus);

    $request = Request::create('/api/v1/catalog/projects/proj-1/merge-requests', 'GET', ['status' => 'open', 'author' => 'jdoe']);
    $response = $controller('proj-1', $request);

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(ListMergeRequestsQuery::class);
    expect($bus->dispatched->projectId)->toBe('proj-1');
    expect($bus->dispatched->status)->toBe('open');
    expect($bus->dispatched->author)->toBe('jdoe');
    expect($bus->dispatched->page)->toBe(1);
    expect($bus->dispatched->perPage)->toBe(20);
});
