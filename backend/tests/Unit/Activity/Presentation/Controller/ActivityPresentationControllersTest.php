<?php

declare(strict_types=1);

use App\Activity\Application\Command\CreateActivityEventCommand;
use App\Activity\Application\Command\CreateNotificationCommand;
use App\Activity\Application\Command\MarkNotificationReadCommand;
use App\Activity\Application\DTO\ActivityEventListOutput;
use App\Activity\Application\DTO\ActivityEventOutput;
use App\Activity\Application\DTO\CreateActivityEventInput;
use App\Activity\Application\DTO\CreateNotificationInput;
use App\Activity\Application\DTO\DashboardOutput;
use App\Activity\Application\DTO\NotificationListOutput;
use App\Activity\Application\DTO\NotificationOutput;
use App\Activity\Application\Query\GetActivityEventQuery;
use App\Activity\Application\Query\GetDashboardQuery;
use App\Activity\Application\Query\GetNotificationQuery;
use App\Activity\Application\Query\ListActivityEventsQuery;
use App\Activity\Application\Query\ListNotificationsQuery;
use App\Activity\Presentation\Controller\CreateActivityEventController;
use App\Activity\Presentation\Controller\CreateNotificationController;
use App\Activity\Presentation\Controller\GetActivityEventController;
use App\Activity\Presentation\Controller\GetDashboardController;
use App\Activity\Presentation\Controller\GetNotificationController;
use App\Activity\Presentation\Controller\ListActivityEventsController;
use App\Activity\Presentation\Controller\ListNotificationsController;
use App\Activity\Presentation\Controller\UpdateNotificationController;
use App\Shared\Application\DTO\PaginatedOutput;
use App\Tests\Factory\Identity\UserFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

function stubActivityInfraBus(mixed $result = null): MessageBusInterface&stdClass
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

it('creates an activity event and returns 201', function () use ($ts) {
    $output = new ActivityEventOutput('ev-1', 'project.created', 'Project', 'p-1', ['name' => 'Monark'], $ts, 'u-1');
    $bus = \stubActivityInfraBus($output);
    $input = new CreateActivityEventInput(type: 'project.created', entityType: 'Project', entityId: 'p-1', payload: ['name' => 'Monark'], userId: 'u-1');
    $response = (new CreateActivityEventController($bus))($input);

    expect($response->getStatusCode())->toBe(201);
    expect($bus->dispatched)->toBeInstanceOf(CreateActivityEventCommand::class);
});

it('gets an activity event', function () use ($ts) {
    $output = new ActivityEventOutput('ev-1', 'project.created', 'Project', 'p-1', [], $ts, 'u-1');
    $bus = \stubActivityInfraBus($output);
    $response = (new GetActivityEventController($bus))('ev-1');

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(GetActivityEventQuery::class);
});

it('lists activity events', function () {
    $bus = \stubActivityInfraBus(new ActivityEventListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20)));
    $response = (new ListActivityEventsController($bus))(Request::create('/api/activity/events'));

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(ListActivityEventsQuery::class);
    expect($bus->dispatched->page)->toBe(1);
    expect($bus->dispatched->perPage)->toBe(20);
});

it('creates a notification and returns 201', function () use ($ts) {
    $output = new NotificationOutput('n-1', 'Alert', 'Something happened', 'in_app', null, 'u-1', $ts);
    $bus = \stubActivityInfraBus($output);
    $input = new CreateNotificationInput(title: 'Alert', message: 'Something happened', channel: 'in_app', userId: 'u-1');
    $response = (new CreateNotificationController($bus))($input);

    expect($response->getStatusCode())->toBe(201);
    expect($bus->dispatched)->toBeInstanceOf(CreateNotificationCommand::class);
});

it('gets a notification', function () use ($ts) {
    $output = new NotificationOutput('n-1', 'Alert', 'Something happened', 'in_app', null, 'u-1', $ts);
    $bus = \stubActivityInfraBus($output);
    $response = (new GetNotificationController($bus))('n-1');

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(GetNotificationQuery::class);
});

it('marks a notification as read', function () use ($ts) {
    $output = new NotificationOutput('n-1', 'Alert', 'Something happened', 'in_app', $ts, 'u-1', $ts);
    $bus = \stubActivityInfraBus($output);
    $response = (new UpdateNotificationController($bus))('n-1');

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(MarkNotificationReadCommand::class);
});

it('lists notifications for current user', function () {
    $bus = \stubActivityInfraBus(new NotificationListOutput(new PaginatedOutput(items: [], total: 0, page: 1, perPage: 20)));
    $user = UserFactory::create();
    $response = (new ListNotificationsController($bus))($user, Request::create('/api/activity/notifications'));

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(ListNotificationsQuery::class);
    expect($bus->dispatched->page)->toBe(1);
    expect($bus->dispatched->perPage)->toBe(20);
});

it('gets dashboard for current user', function () {
    $bus = \stubActivityInfraBus(new DashboardOutput(metrics: []));
    $user = UserFactory::create();
    $response = (new GetDashboardController($bus))($user);

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(GetDashboardQuery::class);
});
