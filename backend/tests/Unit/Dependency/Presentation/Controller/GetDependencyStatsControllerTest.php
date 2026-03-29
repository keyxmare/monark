<?php

declare(strict_types=1);

use App\Dependency\Application\DTO\DependencyStatsOutput;
use App\Dependency\Application\Query\GetDependencyStatsQuery;
use App\Dependency\Presentation\Controller\GetDependencyStatsController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

describe('GetDependencyStatsController', function () {
    it('returns stats with filters', function () {
        $output = new DependencyStatsOutput(total: 10, upToDate: 6, outdated: 4, totalVulnerabilities: 3);
        $bus = new class ($output) extends stdClass implements MessageBusInterface {
            public ?object $dispatched = null;
            public function __construct(private readonly mixed $result)
            {
            }
            public function dispatch(object $message, array $stamps = []): Envelope
            {
                $this->dispatched = $message;
                return (new Envelope($message))->with(new HandledStamp($this->result, 'handler'));
            }
        };

        $request = Request::create('/api/dependency/stats', 'GET', [
            'project_id' => 'p-1',
            'package_manager' => 'composer',
            'type' => 'runtime',
        ]);
        $response = (new GetDependencyStatsController($bus))($request);

        expect($response->getStatusCode())->toBe(200);
        expect($bus->dispatched)->toBeInstanceOf(GetDependencyStatsQuery::class);
        expect($bus->dispatched->projectId)->toBe('p-1');
        expect($bus->dispatched->packageManager)->toBe('composer');
        expect($bus->dispatched->type)->toBe('runtime');
    });
});
