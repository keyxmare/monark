<?php

declare(strict_types=1);

use App\Dependency\Application\Command\CreateDependencyCommand;
use App\Dependency\Application\Command\CreateVulnerabilityCommand;
use App\Dependency\Application\Command\DeleteDependencyCommand;
use App\Dependency\Application\Command\UpdateDependencyCommand;
use App\Dependency\Application\Command\UpdateVulnerabilityCommand;
use App\Dependency\Application\DTO\CreateDependencyInput;
use App\Dependency\Application\DTO\CreateVulnerabilityInput;
use App\Dependency\Application\DTO\DependencyListOutput;
use App\Dependency\Application\DTO\DependencyOutput;
use App\Dependency\Application\DTO\UpdateDependencyInput;
use App\Dependency\Application\DTO\UpdateVulnerabilityInput;
use App\Dependency\Application\DTO\VulnerabilityListOutput;
use App\Dependency\Application\DTO\VulnerabilityOutput;
use App\Dependency\Application\Query\GetDependencyQuery;
use App\Dependency\Application\Query\GetVulnerabilityQuery;
use App\Dependency\Application\Query\ListDependenciesQuery;
use App\Dependency\Application\Query\ListVulnerabilitiesQuery;
use App\Dependency\Presentation\Controller\CreateDependencyController;
use App\Dependency\Presentation\Controller\CreateVulnerabilityController;
use App\Dependency\Presentation\Controller\DeleteDependencyController;
use App\Dependency\Presentation\Controller\GetDependencyController;
use App\Dependency\Presentation\Controller\GetVulnerabilityController;
use App\Dependency\Presentation\Controller\ListDependenciesController;
use App\Dependency\Presentation\Controller\ListVulnerabilitiesController;
use App\Dependency\Presentation\Controller\UpdateDependencyController;
use App\Dependency\Presentation\Controller\UpdateVulnerabilityController;
use App\Shared\Application\DTO\PaginatedOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

function stubDependencyBus(mixed $result = null): MessageBusInterface&stdClass
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

$ts = '2026-01-01T00:00:00+00:00';
$uuid = 'a0000000-0000-0000-0000-000000000001';

it('creates a dependency and returns 201', function () use ($ts, $uuid) {
    $output = new DependencyOutput('d-1', 'symfony/http-kernel', '7.2.0', '8.0.0', '7.4.0', 'composer', 'runtime', true, $uuid, null, 0, $ts, $ts);
    $bus = \stubDependencyBus($output);
    $controller = new CreateDependencyController($bus);

    $input = new CreateDependencyInput(
        name: 'symfony/http-kernel',
        currentVersion: '7.2.0',
        latestVersion: '8.0.0',
        ltsVersion: '7.4.0',
        packageManager: 'composer',
        type: 'runtime',
        isOutdated: true,
        projectId: $uuid,
    );
    $response = $controller($input);

    expect($response->getStatusCode())->toBe(201);
    expect($bus->dispatched)->toBeInstanceOf(CreateDependencyCommand::class);
});

it('gets a dependency', function () use ($ts, $uuid) {
    $output = new DependencyOutput('d-1', 'symfony/http-kernel', '7.2.0', '8.0.0', '7.4.0', 'composer', 'runtime', true, $uuid, null, 0, $ts, $ts);
    $bus = \stubDependencyBus($output);
    $response = (new GetDependencyController($bus))('d-1');

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(GetDependencyQuery::class);
});

it('updates a dependency', function () use ($ts, $uuid) {
    $output = new DependencyOutput('d-1', 'symfony/http-kernel', '8.0.0', '8.0.0', '7.4.0', 'composer', 'runtime', false, $uuid, null, 0, $ts, $ts);
    $bus = \stubDependencyBus($output);
    $response = (new UpdateDependencyController($bus))('d-1', new UpdateDependencyInput(currentVersion: '8.0.0', isOutdated: false));

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(UpdateDependencyCommand::class);
});

it('deletes a dependency and returns 204', function () {
    $bus = \stubDependencyBus();
    $response = (new DeleteDependencyController($bus))('d-1');

    expect($response->getStatusCode())->toBe(204);
    expect($bus->dispatched)->toBeInstanceOf(DeleteDependencyCommand::class);
});

it('lists dependencies with project filter', function () {
    $bus = \stubDependencyBus(new DependencyListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20)));
    $response = (new ListDependenciesController($bus))(Request::create('/api/v1/dependency/dependencies', 'GET', ['project_id' => 'p-1']));

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(ListDependenciesQuery::class);
    expect($bus->dispatched->page)->toBe(1);
    expect($bus->dispatched->perPage)->toBe(20);
    expect($bus->dispatched->projectId)->toBe('p-1');
    expect($bus->dispatched->sort)->toBe('name');
    expect($bus->dispatched->sortDir)->toBe('asc');
    expect($bus->dispatched->search)->toBeNull();
    expect($bus->dispatched->packageManager)->toBeNull();
    expect($bus->dispatched->type)->toBeNull();
    expect($bus->dispatched->isOutdated)->toBeNull();
});

it('lists dependencies with custom page and perPage', function () {
    $bus = \stubDependencyBus(new DependencyListOutput(new PaginatedOutput(items: [], total: 0, page: 3, perPage: 50)));
    $response = (new ListDependenciesController($bus))(Request::create('/api/v1/dependency/dependencies', 'GET', ['page' => '3', 'per_page' => '50']));

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched->page)->toBe(3);
    expect($bus->dispatched->perPage)->toBe(50);
});

it('lists dependencies with all query parameters', function () {
    $bus = \stubDependencyBus(new DependencyListOutput(new PaginatedOutput(items: [], total: 0, page: 2, perPage: 10)));
    $response = (new ListDependenciesController($bus))(Request::create('/api/v1/dependency/dependencies', 'GET', [
        'page' => '2',
        'per_page' => '10',
        'project_id' => 'proj-123',
        'search' => 'vue',
        'package_manager' => 'npm',
        'type' => 'runtime',
        'sort' => 'name',
        'sort_dir' => 'desc',
        'is_outdated' => '1',
    ]));

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched->page)->toBe(2);
    expect($bus->dispatched->perPage)->toBe(10);
    expect($bus->dispatched->projectId)->toBe('proj-123');
    expect($bus->dispatched->search)->toBe('vue');
    expect($bus->dispatched->packageManager)->toBe('npm');
    expect($bus->dispatched->type)->toBe('runtime');
    expect($bus->dispatched->sort)->toBe('name');
    expect($bus->dispatched->sortDir)->toBe('desc');
    expect($bus->dispatched->isOutdated)->toBeTrue();
});

it('parses is_outdated=true as true', function () {
    $bus = \stubDependencyBus(new DependencyListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20)));
    (new ListDependenciesController($bus))(Request::create('/api/v1/dependency/dependencies', 'GET', ['is_outdated' => 'true']));

    expect($bus->dispatched->isOutdated)->toBeTrue();
});

it('parses is_outdated=1 as true', function () {
    $bus = \stubDependencyBus(new DependencyListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20)));
    (new ListDependenciesController($bus))(Request::create('/api/v1/dependency/dependencies', 'GET', ['is_outdated' => '1']));

    expect($bus->dispatched->isOutdated)->toBeTrue();
});

it('parses is_outdated=0 as false', function () {
    $bus = \stubDependencyBus(new DependencyListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20)));
    (new ListDependenciesController($bus))(Request::create('/api/v1/dependency/dependencies', 'GET', ['is_outdated' => '0']));

    expect($bus->dispatched->isOutdated)->toBeFalse();
});

it('parses is_outdated=false as false', function () {
    $bus = \stubDependencyBus(new DependencyListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20)));
    (new ListDependenciesController($bus))(Request::create('/api/v1/dependency/dependencies', 'GET', ['is_outdated' => 'false']));

    expect($bus->dispatched->isOutdated)->toBeFalse();
});

it('leaves is_outdated null when not provided', function () {
    $bus = \stubDependencyBus(new DependencyListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20)));
    (new ListDependenciesController($bus))(Request::create('/api/v1/dependency/dependencies', 'GET'));

    expect($bus->dispatched->isOutdated)->toBeNull();
});

it('uses default sort values when not provided', function () {
    $bus = \stubDependencyBus(new DependencyListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20)));
    (new ListDependenciesController($bus))(Request::create('/api/v1/dependency/dependencies', 'GET'));

    expect($bus->dispatched->sort)->toBe('name');
    expect($bus->dispatched->sortDir)->toBe('asc');
});

it('passes custom sort values', function () {
    $bus = \stubDependencyBus(new DependencyListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20)));
    (new ListDependenciesController($bus))(Request::create('/api/v1/dependency/dependencies', 'GET', [
        'sort' => 'updatedAt',
        'sort_dir' => 'desc',
    ]));

    expect($bus->dispatched->sort)->toBe('updatedAt');
    expect($bus->dispatched->sortDir)->toBe('desc');
});

it('returns JSON response with pagination data', function () {
    $output = new DependencyListOutput(new PaginatedOutput(items: ['item1', 'item2'], total: 42, page: 2, perPage: 10));
    $bus = \stubDependencyBus($output);
    $response = (new ListDependenciesController($bus))(Request::create('/api/v1/dependency/dependencies', 'GET'));

    expect($response->getStatusCode())->toBe(200);
    $data = \json_decode($response->getContent(), true);
    expect($data['success'])->toBeTrue();
    expect($data['data']['total'])->toBe(42);
    expect($data['data']['page'])->toBe(2);
    expect($data['data']['per_page'])->toBe(10);
});

it('creates a vulnerability and returns 201', function () use ($ts, $uuid) {
    $output = new VulnerabilityOutput('v-1', 'CVE-2026-0001', 'critical', 'RCE in parser', 'Remote code execution', '8.0.1', 'open', $ts, $uuid, 'symfony/http-kernel', $ts, $ts);
    $bus = \stubDependencyBus($output);
    $input = new CreateVulnerabilityInput(
        cveId: 'CVE-2026-0001',
        severity: 'critical',
        title: 'RCE in parser',
        description: 'Remote code execution',
        patchedVersion: '8.0.1',
        status: 'open',
        detectedAt: $ts,
        dependencyId: $uuid,
    );
    $response = (new CreateVulnerabilityController($bus))($input);

    expect($response->getStatusCode())->toBe(201);
    expect($bus->dispatched)->toBeInstanceOf(CreateVulnerabilityCommand::class);
});

it('gets a vulnerability', function () use ($ts, $uuid) {
    $output = new VulnerabilityOutput('v-1', 'CVE-2026-0001', 'critical', 'RCE', 'Desc', '8.0.1', 'open', $ts, $uuid, 'dep-name', $ts, $ts);
    $bus = \stubDependencyBus($output);
    $response = (new GetVulnerabilityController($bus))('v-1');

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(GetVulnerabilityQuery::class);
});

it('updates a vulnerability', function () use ($ts, $uuid) {
    $output = new VulnerabilityOutput('v-1', 'CVE-2026-0001', 'critical', 'RCE', 'Desc', '8.0.1', 'fixed', $ts, $uuid, 'dep-name', $ts, $ts);
    $bus = \stubDependencyBus($output);
    $response = (new UpdateVulnerabilityController($bus))('v-1', new UpdateVulnerabilityInput(status: 'fixed'));

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(UpdateVulnerabilityCommand::class);
});

it('lists vulnerabilities', function () {
    $bus = \stubDependencyBus(new VulnerabilityListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20)));
    $response = (new ListVulnerabilitiesController($bus))(Request::create('/api/v1/dependency/vulnerabilities'));

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(ListVulnerabilitiesQuery::class);
    expect($bus->dispatched->page)->toBe(1);
    expect($bus->dispatched->perPage)->toBe(20);
});
