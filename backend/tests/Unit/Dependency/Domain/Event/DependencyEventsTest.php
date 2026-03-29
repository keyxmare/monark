<?php

declare(strict_types=1);

use App\Dependency\Domain\Event\DependencyCreated;
use App\Dependency\Domain\Event\DependencyDeleted;
use App\Dependency\Domain\Event\DependencyUpdated;

describe('DependencyCreated', function () {
    it('holds dependency id and name', function () {
        $event = new DependencyCreated(dependencyId: 'dep-1', name: 'symfony/http-kernel');

        expect($event->dependencyId)->toBe('dep-1');
        expect($event->name)->toBe('symfony/http-kernel');
    });
});

describe('DependencyDeleted', function () {
    it('holds dependency id and name', function () {
        $event = new DependencyDeleted(dependencyId: 'dep-1', name: 'vue');

        expect($event->dependencyId)->toBe('dep-1');
        expect($event->name)->toBe('vue');
    });
});

describe('DependencyUpdated', function () {
    it('holds dependency id and name', function () {
        $event = new DependencyUpdated(dependencyId: 'dep-1', name: 'react');

        expect($event->dependencyId)->toBe('dep-1');
        expect($event->name)->toBe('react');
    });
});
