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
    $response = (new ListDependenciesController($bus))(Request::create('/api/dependency/dependencies', 'GET', ['project_id' => 'p-1']));

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(ListDependenciesQuery::class);
    expect($bus->dispatched->page)->toBe(1);
    expect($bus->dispatched->perPage)->toBe(20);
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
    $response = (new ListVulnerabilitiesController($bus))(Request::create('/api/dependency/vulnerabilities'));

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(ListVulnerabilitiesQuery::class);
    expect($bus->dispatched->page)->toBe(1);
    expect($bus->dispatched->perPage)->toBe(20);
});
