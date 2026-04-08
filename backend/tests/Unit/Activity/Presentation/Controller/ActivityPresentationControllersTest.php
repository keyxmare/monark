<?php

declare(strict_types=1);

use App\Activity\Application\DTO\DashboardOutput;
use App\Activity\Application\Query\GetDashboardQuery;
use App\Activity\Presentation\Controller\GetDashboardController;
use App\Tests\Factory\Identity\UserFactory;
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

it('gets dashboard for current user', function () {
    $bus = \stubActivityInfraBus(new DashboardOutput(metrics: []));
    $user = UserFactory::create();
    $response = (new GetDashboardController($bus))($user);

    expect($response->getStatusCode())->toBe(200);
    expect($bus->dispatched)->toBeInstanceOf(GetDashboardQuery::class);
});
