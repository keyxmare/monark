<?php

declare(strict_types=1);

use App\Activity\Application\DTO\DashboardOutput;
use App\Activity\Application\Query\GetDashboardQuery;
use App\Activity\Application\QueryHandler\GetDashboardHandler;

describe('GetDashboardHandler', function () {
    it('returns dashboard with empty metrics', function () {
        $handler = new GetDashboardHandler();

        $result = $handler(new GetDashboardQuery('00000000-0000-0000-0000-000000000001'));

        expect($result)->toBeInstanceOf(DashboardOutput::class);
        expect($result->metrics)->toBeEmpty();
    });
});
