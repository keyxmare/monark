<?php

declare(strict_types=1);

namespace App\Activity\Application\QueryHandler;

use App\Activity\Application\DTO\DashboardOutput;
use App\Activity\Application\Query\GetDashboardQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetDashboardHandler
{
    public function __invoke(GetDashboardQuery $query): DashboardOutput
    {
        return new DashboardOutput(metrics: []);
    }
}
