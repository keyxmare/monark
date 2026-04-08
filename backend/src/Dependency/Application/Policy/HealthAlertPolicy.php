<?php

declare(strict_types=1);

namespace App\Dependency\Application\Policy;

final readonly class HealthAlertPolicy
{
    private const int ALERT_THRESHOLD = 30;

    public function requiresAlert(int $healthScore): bool
    {
        return $healthScore < self::ALERT_THRESHOLD;
    }
}
