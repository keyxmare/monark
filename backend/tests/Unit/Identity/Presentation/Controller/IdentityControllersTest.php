<?php

declare(strict_types=1);

use App\Identity\Application\Command\RegisterUserCommand;
use App\Identity\Application\Command\UpdateUserCommand;
use App\Identity\Application\DTO\RegisterUserInput;
use App\Identity\Application\DTO\UpdateUserInput;
use App\Identity\Application\DTO\UserListOutput;
use App\Identity\Application\DTO\UserOutput;
use App\Identity\Application\Query\GetUserQuery;
use App\Identity\Application\Query\ListUsersQuery;
use App\Identity\Presentation\Controller\GetCurrentUserController;
use App\Identity\Presentation\Controller\GetUserController;
use App\Identity\Presentation\Controller\ListUsersController;
use App\Identity\Presentation\Controller\LoginController;
use App\Identity\Presentation\Controller\LogoutController;
use App\Identity\Presentation\Controller\RegisterController;
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
    $bus = \stubIdentityBus($output);
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
    $bus = \stubIdentityBus($output);
    $controller = new GetUserController($bus);

    $response = $controller('u-1');

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(GetUserQuery::class);
});

it('updates a user', function () {
    $output = new UserOutput('u-1', 'john@test.com', 'Jane', 'Doe', null, ['ROLE_USER'], '2026-01-01T00:00:00+00:00', '2026-01-01T00:00:00+00:00');
    $bus = \stubIdentityBus($output);
    $controller = new UpdateUserController($bus);

    $response = $controller('u-1', new UpdateUserInput(firstName: 'Jane'));

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(UpdateUserCommand::class);
});

it('lists users with pagination', function () {
    $listOutput = new UserListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20));
    $bus = \stubIdentityBus($listOutput);
    $controller = new ListUsersController($bus);

    $response = $controller(Request::create('/api/v1/identity/users', 'GET', ['page' => 1]));

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(ListUsersQuery::class);
    expect($bus->dispatched->page)->toBe(1);
    expect($bus->dispatched->perPage)->toBe(20);
});
