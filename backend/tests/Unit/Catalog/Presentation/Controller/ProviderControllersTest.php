<?php

declare(strict_types=1);

use App\Catalog\Application\Command\CreateProviderCommand;
use App\Catalog\Application\Command\DeleteProviderCommand;
use App\Catalog\Application\Command\ImportProjectsCommand;
use App\Catalog\Application\Command\SyncAllProjectsCommand;
use App\Catalog\Application\Command\TestProviderConnectionCommand;
use App\Catalog\Application\Command\UpdateProviderCommand;
use App\Catalog\Application\DTO\CreateProviderInput;
use App\Catalog\Application\DTO\ImportProjectsInput;
use App\Catalog\Application\DTO\ProviderListOutput;
use App\Catalog\Application\DTO\ProviderOutput;
use App\Catalog\Application\DTO\RemoteProjectListOutput;
use App\Catalog\Application\DTO\SyncJobOutput;
use App\Catalog\Application\DTO\UpdateProviderInput;
use App\Catalog\Application\Query\GetProviderQuery;
use App\Catalog\Application\Query\ListProvidersQuery;
use App\Catalog\Application\Query\ListRemoteProjectsQuery;
use App\Catalog\Domain\Model\ProviderType;
use App\Catalog\Presentation\Controller\CreateProviderController;
use App\Catalog\Presentation\Controller\DeleteProviderController;
use App\Catalog\Presentation\Controller\GetProviderController;
use App\Catalog\Presentation\Controller\ImportProjectsController;
use App\Catalog\Presentation\Controller\ListProvidersController;
use App\Catalog\Presentation\Controller\ListRemoteProjectsController;
use App\Catalog\Presentation\Controller\SyncAllProjectsController;
use App\Catalog\Presentation\Controller\TestProviderConnectionController;
use App\Catalog\Presentation\Controller\UpdateProviderController;
use App\Shared\Application\DTO\PaginatedOutput;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

function stubProviderBus(mixed $result = null): MessageBusInterface&stdClass
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

it('creates a provider and returns 201', function () {
    $output = new ProviderOutput('prov-1', 'My GitLab', 'gitlab', 'https://gitlab.com', null, 'active', 0, null, '2026-01-01T00:00:00+00:00', '2026-01-01T00:00:00+00:00');
    $bus = \stubProviderBus($output);
    $controller = new CreateProviderController($bus);

    $input = new CreateProviderInput(name: 'My GitLab', type: ProviderType::GitLab, url: 'https://gitlab.com');
    $response = $controller($input);

    expect($response->getStatusCode())->toBe(201);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['success'])->toBeTrue();
    expect($bus->dispatched)->toBeInstanceOf(CreateProviderCommand::class);
});

it('gets a provider and returns 200', function () {
    $output = new ProviderOutput('prov-1', 'My GitLab', 'gitlab', 'https://gitlab.com', null, 'active', 0, null, '2026-01-01T00:00:00+00:00', '2026-01-01T00:00:00+00:00');
    $bus = \stubProviderBus($output);
    $controller = new GetProviderController($bus);

    $response = $controller('prov-1');

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(GetProviderQuery::class);
});

it('updates a provider and returns 200', function () {
    $output = new ProviderOutput('prov-1', 'Updated', 'gitlab', 'https://gitlab.com', null, 'active', 0, null, '2026-01-01T00:00:00+00:00', '2026-01-01T00:00:00+00:00');
    $bus = \stubProviderBus($output);
    $controller = new UpdateProviderController($bus);

    $input = new UpdateProviderInput(name: 'Updated');
    $response = $controller('prov-1', $input);

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(UpdateProviderCommand::class);
});

it('deletes a provider and returns 204', function () {
    $bus = \stubProviderBus();
    $controller = new DeleteProviderController($bus);

    $response = $controller('prov-1');

    expect($response->getStatusCode())->toBe(204);
    expect($bus->dispatched)->toBeInstanceOf(DeleteProviderCommand::class);
});

it('lists providers with pagination', function () {
    $listOutput = new ProviderListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20));
    $bus = \stubProviderBus($listOutput);
    $controller = new ListProvidersController($bus);

    $request = Request::create('/api/v1/catalog/providers', 'GET', ['page' => 1, 'per_page' => 20]);
    $response = $controller($request);

    expect($response->getStatusCode())->toBe(200);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['data'])->toHaveKeys(['items', 'total', 'page', 'per_page']);
    expect($bus->dispatched)->toBeInstanceOf(ListProvidersQuery::class);
    expect($bus->dispatched->page)->toBe(1);
    expect($bus->dispatched->perPage)->toBe(20);
});

it('tests provider connection and returns 200', function () {
    $bus = \stubProviderBus(['connected' => true]);
    $controller = new TestProviderConnectionController($bus);

    $response = $controller('prov-1');

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(TestProviderConnectionCommand::class);
});

it('imports projects and returns 201', function () {
    $bus = \stubProviderBus(['imported' => 2]);
    $user = \App\Tests\Factory\Identity\UserFactory::create();

    $security = $this->createMock(Security::class);
    $security->method('getUser')->willReturn($user);

    $controller = new ImportProjectsController($bus, $security);
    $input = new ImportProjectsInput([
        ['externalId' => 'owner/repo', 'name' => 'repo', 'slug' => 'owner-repo', 'repositoryUrl' => 'https://github.com/owner/repo'],
    ]);
    $response = $controller('prov-1', $input);

    expect($response->getStatusCode())->toBe(201);
    expect($bus->dispatched)->toBeInstanceOf(ImportProjectsCommand::class);
    expect($bus->dispatched->ownerId)->toBe($user->getId()->toRfc4122());
});

it('syncs all projects globally and returns 202', function () {
    $output = new SyncJobOutput('job-1', 5, '2026-01-01T00:00:00+00:00');
    $bus = \stubProviderBus($output);
    $controller = new SyncAllProjectsController($bus);

    $request = Request::create('/api/v1/catalog/sync-all?force=1', 'POST');
    $response = $controller->syncAll($request);

    expect($response->getStatusCode())->toBe(202);
    expect($bus->dispatched)->toBeInstanceOf(SyncAllProjectsCommand::class);
    expect($bus->dispatched->force)->toBeTrue();
    expect($bus->dispatched->providerId)->toBeNull();
});

it('syncs projects by provider and returns 202', function () {
    $output = new SyncJobOutput('job-1', 3, '2026-01-01T00:00:00+00:00');
    $bus = \stubProviderBus($output);
    $controller = new SyncAllProjectsController($bus);

    $request = Request::create('/api/v1/catalog/providers/prov-1/sync-all', 'POST', [], [], [], [], \json_encode(['projectIds' => ['p1', 'p2']]));
    $request->headers->set('Content-Type', 'application/json');
    $response = $controller->syncByProvider('prov-1', $request);

    expect($response->getStatusCode())->toBe(202);
    expect($bus->dispatched)->toBeInstanceOf(SyncAllProjectsCommand::class);
    expect($bus->dispatched->providerId)->toBe('prov-1');
});

it('lists remote projects with filters', function () {
    $listOutput = new RemoteProjectListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20));
    $bus = \stubProviderBus($listOutput);
    $controller = new ListRemoteProjectsController($bus);

    $request = Request::create('/api/v1/catalog/providers/prov-1/remote-projects', 'GET', [
        'page' => 1,
        'per_page' => 10,
        'search' => 'monark',
        'visibility' => 'public',
        'sort' => 'name',
        'sort_dir' => 'asc',
    ]);
    $response = $controller('prov-1', $request);

    expect($response->getStatusCode())->toBe(200);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['data'])->toHaveKeys(['items', 'total']);
    expect($bus->dispatched)->toBeInstanceOf(ListRemoteProjectsQuery::class);
    expect($bus->dispatched->page)->toBe(1);
    expect($bus->dispatched->perPage)->toBe(10);
    expect($bus->dispatched->search)->toBe('monark');
    expect($bus->dispatched->visibility)->toBe('public');
    expect($bus->dispatched->sort)->toBe('name');
    expect($bus->dispatched->sortDir)->toBe('asc');
});

it('lists remote projects with default pagination', function () {
    $listOutput = new RemoteProjectListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20));
    $bus = \stubProviderBus($listOutput);
    $controller = new ListRemoteProjectsController($bus);

    $request = Request::create('/api/v1/catalog/providers/prov-1/remote-projects', 'GET');
    $response = $controller('prov-1', $request);

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(ListRemoteProjectsQuery::class);
    expect($bus->dispatched->page)->toBe(1);
    expect($bus->dispatched->perPage)->toBe(20);
    expect($bus->dispatched->search)->toBeNull();
    expect($bus->dispatched->visibility)->toBeNull();
    expect($bus->dispatched->sort)->toBe('name');
    expect($bus->dispatched->sortDir)->toBe('asc');
});
