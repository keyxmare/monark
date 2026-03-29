<?php

declare(strict_types=1);

use App\Shared\Domain\DTO\DetectedDependency;
use App\Shared\Domain\DTO\DetectedStack;
use App\Shared\Domain\DTO\ScanResult;
use App\Shared\Domain\Event\MergeRequestsSyncedEvent;
use App\Shared\Domain\Event\ProjectScannedEvent;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;

describe('MergeRequestsSyncedEvent', function () {
    it('stores projectId, created and updated counts', function () {
        $event = new MergeRequestsSyncedEvent('proj-123', 5, 3);

        expect($event->projectId)->toBe('proj-123');
        expect($event->created)->toBe(5);
        expect($event->updated)->toBe(3);
    });

    it('accepts zero counts', function () {
        $event = new MergeRequestsSyncedEvent('proj-456', 0, 0);

        expect($event->created)->toBe(0);
        expect($event->updated)->toBe(0);
    });
});

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
