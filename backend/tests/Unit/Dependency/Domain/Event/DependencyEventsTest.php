<?php

declare(strict_types=1);

use App\Dependency\Domain\Event\DependencyCreated;
use App\Dependency\Domain\Event\DependencyDeleted;
use App\Dependency\Domain\Event\DependencyUpdated;

describe('DependencyCreated', function () {
    it('holds dependency id, name, packageManager, currentVersion and projectId', function () {
        $event = new DependencyCreated(
            dependencyId: 'dep-1',
            name: 'symfony/http-kernel',
            packageManager: 'composer',
            currentVersion: '6.4.0',
            projectId: 'proj-1',
        );

        expect($event->dependencyId)->toBe('dep-1');
        expect($event->name)->toBe('symfony/http-kernel');
        expect($event->packageManager)->toBe('composer');
        expect($event->currentVersion)->toBe('6.4.0');
        expect($event->projectId)->toBe('proj-1');
    });
});

describe('DependencyDeleted', function () {
    it('holds dependency id, name and packageManager', function () {
        $event = new DependencyDeleted(dependencyId: 'dep-1', name: 'vue', packageManager: 'npm');

        expect($event->dependencyId)->toBe('dep-1');
        expect($event->name)->toBe('vue');
        expect($event->packageManager)->toBe('npm');
    });
});

describe('DependencyUpdated', function () {
    it('holds dependency id and name', function () {
        $event = new DependencyUpdated(dependencyId: 'dep-1', name: 'react');

        expect($event->dependencyId)->toBe('dep-1');
        expect($event->name)->toBe('react');
    });
});
