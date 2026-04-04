<?php

declare(strict_types=1);

use App\Shared\Domain\DTO\DetectedDependency;
use App\Shared\Domain\DTO\DetectedStack;
use App\Shared\Domain\DTO\ScanResult;
use App\Shared\Domain\Event\ProjectScannedEvent;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;

describe('ProjectScannedEvent', function () {
    it('stores projectId and scanResult', function () {
        $stack = new DetectedStack('PHP', 'Symfony', '8.4', '7.0');
        $dep = new DetectedDependency('symfony/console', '6.4.0', PackageManager::Composer, DependencyType::Runtime);
        $scanResult = new ScanResult([$stack], [$dep]);

        $event = new ProjectScannedEvent('proj-789', $scanResult);

        expect($event->projectId)->toBe('proj-789');
        expect($event->scanResult)->toBe($scanResult);
        expect($event->scanResult->stacks)->toHaveCount(1);
        expect($event->scanResult->dependencies)->toHaveCount(1);
    });

    it('works with empty scan result', function () {
        $scanResult = new ScanResult([], []);
        $event = new ProjectScannedEvent('proj-000', $scanResult);

        expect($event->scanResult->stacks)->toBe([]);
        expect($event->scanResult->dependencies)->toBe([]);
    });
});
