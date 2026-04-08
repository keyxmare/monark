<?php

declare(strict_types=1);

namespace App\Activity\Domain\Port;

use App\Activity\Domain\Event\BuildMetricRecorded;

interface BuildMetricNotifierPort
{
    public function notify(BuildMetricRecorded $event): void;
}
