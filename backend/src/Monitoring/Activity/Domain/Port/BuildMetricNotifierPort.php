<?php

declare(strict_types=1);

namespace App\Monitoring\Activity\Domain\Port;

use App\Monitoring\Activity\Domain\Event\BuildMetricRecorded;

interface BuildMetricNotifierPort
{
    public function notify(BuildMetricRecorded $event): void;
}
