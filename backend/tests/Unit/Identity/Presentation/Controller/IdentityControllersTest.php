<?php

declare(strict_types=1);

use App\Identity\Application\Command\CreateAccessTokenCommand;
use App\Identity\Application\Command\CreateTeamCommand;
use App\Identity\Application\Command\DeleteAccessTokenCommand;
use App\Identity\Application\Command\DeleteTeamCommand;
use App\Identity\Application\Command\RegisterUserCommand;
use App\Identity\Application\Command\UpdateTeamCommand;
use App\Identity\Application\Command\UpdateUserCommand;
use App\Identity\Application\DTO\AccessTokenListOutput;
use App\Identity\Application\DTO\AccessTokenOutput;
use App\Identity\Application\DTO\CreateAccessTokenInput;
use App\Identity\Application\DTO\CreateTeamInput;
use App\Identity\Application\DTO\RegisterUserInput;
use App\Identity\Application\DTO\TeamListOutput;
use App\Identity\Application\DTO\TeamOutput;
use App\Identity\Application\DTO\UpdateTeamInput;
use App\Identity\Application\DTO\UpdateUserInput;
use App\Identity\Application\DTO\UserListOutput;
use App\Identity\Application\DTO\UserOutput;
use App\Identity\Application\Query\GetAccessTokenQuery;
use App\Identity\Application\Query\GetTeamQuery;
use App\Identity\Application\Query\GetUserQuery;
use App\Identity\Application\Query\ListAccessTokensQuery;
use App\Identity\Application\Query\ListTeamsQuery;
use App\Identity\Application\Query\ListUsersQuery;
use App\Identity\Presentation\Controller\CreateAccessTokenController;
use App\Identity\Presentation\Controller\CreateTeamController;
use App\Identity\Presentation\Controller\DeleteAccessTokenController;
use App\Identity\Presentation\Controller\DeleteTeamController;
use App\Identity\Presentation\Controller\GetAccessTokenController;
use App\Identity\Presentation\Controller\GetCurrentUserController;
use App\Identity\Presentation\Controller\GetTeamController;
use App\Identity\Presentation\Controller\GetUserController;
use App\Identity\Presentation\Controller\ListAccessTokensController;
use App\Identity\Presentation\Controller\ListTeamsController;
use App\Identity\Presentation\Controller\ListUsersController;
use App\Identity\Presentation\Controller\LoginController;
use App\Identity\Presentation\Controller\LogoutController;
use App\Identity\Presentation\Controller\RegisterController;
use App\Identity\Presentation\Controller\UpdateTeamController;
use App\Identity\Presentation\Controller\UpdateUserController;
use App\Shared\Application\DTO\PaginatedOutput;
use App\Tests\Factory\Identity\UserFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

function stubIdentityBus(mixed $result = null): MessageBusInterface&stdClass
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

it('registers a user and returns 201', function () {
    $output = new UserOutput('u-1', 'john@test.com', 'John', 'Doe', null, ['ROLE_USER'], '2026-01-01T00:00:00+00:00', '2026-01-01T00:00:00+00:00');
    $bus = stubIdentityBus($output);
    $controller = new RegisterController($bus);

    $input = new RegisterUserInput(email: 'john@test.com', password: 'password123', firstName: 'John', lastName: 'Doe');
    $response = $controller($input);

    expect($response->getStatusCode())->toBe(201);
    expect($bus->dispatched)->toBeInstanceOf(RegisterUserCommand::class);
});

it('login controller throws logic exception', function () {
    $controller = new LoginController();
    $controller();
})->throws(\LogicException::class);

it('logout returns success', function () {
    $controller = new LogoutController();
    $response = $controller();

    expect($response->getStatusCode())->toBe(200);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['success'])->toBeTrue();
    expect($data['data']['message'])->toBe('Logged out successfully.');
});

it('gets current user profile', function () {
    $user = UserFactory::create();
    $controller = new GetCurrentUserController();
    $response = $controller($user);

    expect($response->getStatusCode())->toBe(200);
    $data = \json_decode((string) $response->getContent(), true);
    expect($data['success'])->toBeTrue();
    expect($data['data']['email'])->toBe('john@example.com');
});

it('gets a user by id', function () {
    $output = new UserOutput('u-1', 'john@test.com', 'John', 'Doe', null, ['ROLE_USER'], '2026-01-01T00:00:00+00:00', '2026-01-01T00:00:00+00:00');
    $bus = stubIdentityBus($output);
    $controller = new GetUserController($bus);

    $response = $controller('u-1');

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(GetUserQuery::class);
});

it('updates a user', function () {
    $output = new UserOutput('u-1', 'john@test.com', 'Jane', 'Doe', null, ['ROLE_USER'], '2026-01-01T00:00:00+00:00', '2026-01-01T00:00:00+00:00');
    $bus = stubIdentityBus($output);
    $controller = new UpdateUserController($bus);

    $response = $controller('u-1', new UpdateUserInput(firstName: 'Jane'));

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(UpdateUserCommand::class);
});

it('lists users with pagination', function () {
    $listOutput = new UserListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20));
    $bus = stubIdentityBus($listOutput);
    $controller = new ListUsersController($bus);

    $response = $controller(Request::create('/api/identity/users', 'GET', ['page' => 1]));

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(ListUsersQuery::class);
});

it('creates a team and returns 201', function () {
    $output = new TeamOutput('t-1', 'Dev Team', 'dev-team', null, 0, '2026-01-01T00:00:00+00:00', '2026-01-01T00:00:00+00:00');
    $bus = stubIdentityBus($output);
    $controller = new CreateTeamController($bus);

    $response = $controller(new CreateTeamInput(name: 'Dev Team', slug: 'dev-team'));

    expect($response->getStatusCode())->toBe(201);
    expect($bus->dispatched)->toBeInstanceOf(CreateTeamCommand::class);
});

it('gets a team', function () {
    $output = new TeamOutput('t-1', 'Dev Team', 'dev-team', null, 0, '2026-01-01T00:00:00+00:00', '2026-01-01T00:00:00+00:00');
    $bus = stubIdentityBus($output);
    $controller = new GetTeamController($bus);

    $response = $controller('t-1');

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(GetTeamQuery::class);
});

it('updates a team', function () {
    $output = new TeamOutput('t-1', 'Updated', 'updated', null, 0, '2026-01-01T00:00:00+00:00', '2026-01-01T00:00:00+00:00');
    $bus = stubIdentityBus($output);
    $controller = new UpdateTeamController($bus);

    $response = $controller('t-1', new UpdateTeamInput(name: 'Updated'));

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(UpdateTeamCommand::class);
});

it('deletes a team and returns 204', function () {
    $bus = stubIdentityBus();
    $controller = new DeleteTeamController($bus);

    $response = $controller('t-1');

    expect($response->getStatusCode())->toBe(204);
    expect($bus->dispatched)->toBeInstanceOf(DeleteTeamCommand::class);
});

it('lists teams with pagination', function () {
    $listOutput = new TeamListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20));
    $bus = stubIdentityBus($listOutput);
    $controller = new ListTeamsController($bus);

    $response = $controller(Request::create('/api/identity/teams', 'GET'));

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(ListTeamsQuery::class);
});

it('creates an access token and returns 201', function () {
    $output = new AccessTokenOutput('at-1', 'gitlab', ['api'], null, 'u-1', '2026-01-01T00:00:00+00:00');
    $bus = stubIdentityBus($output);
    $controller = new CreateAccessTokenController($bus);
    $user = UserFactory::create();

    $response = $controller($user, new CreateAccessTokenInput(provider: 'gitlab', token: 'glpat-xxx', scopes: ['api']));

    expect($response->getStatusCode())->toBe(201);
    expect($bus->dispatched)->toBeInstanceOf(CreateAccessTokenCommand::class);
});

it('gets an access token', function () {
    $output = new AccessTokenOutput('at-1', 'gitlab', ['api'], null, 'u-1', '2026-01-01T00:00:00+00:00');
    $bus = stubIdentityBus($output);
    $controller = new GetAccessTokenController($bus);

    $response = $controller('at-1');

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(GetAccessTokenQuery::class);
});

it('deletes an access token and returns 204', function () {
    $bus = stubIdentityBus();
    $controller = new DeleteAccessTokenController($bus);

    $response = $controller('at-1');

    expect($response->getStatusCode())->toBe(204);
    expect($bus->dispatched)->toBeInstanceOf(DeleteAccessTokenCommand::class);
});

it('lists access tokens with pagination', function () {
    $listOutput = new AccessTokenListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20));
    $bus = stubIdentityBus($listOutput);
    $controller = new ListAccessTokensController($bus);
    $user = UserFactory::create();

    $response = $controller($user, Request::create('/api/identity/access-tokens', 'GET'));

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(ListAccessTokensQuery::class);
});
