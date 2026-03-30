<?php

declare(strict_types=1);

use App\Catalog\Application\DTO\MergeRequestListOutput;
use App\Catalog\Application\Query\GetMergeRequestQuery;
use App\Catalog\Application\Query\ListMergeRequestsQuery;
use App\Catalog\Presentation\Controller\GetMergeRequestController;
use App\Catalog\Presentation\Controller\ListMergeRequestsController;
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
