<?php

declare(strict_types=1);

use App\Catalog\Domain\Event\ProjectCreated;
use App\Catalog\Domain\Event\ProjectDeleted;
use App\Catalog\Domain\Event\ProjectMetadataSyncedEvent;
use App\Catalog\Domain\Event\ProjectSyncCompletedEvent;
use App\Catalog\Domain\Event\ProjectUpdated;

describe('ProjectCreated', function () {
    it('holds event data', function () {
        $event = new ProjectCreated(
            projectId: 'proj-123',
            name: 'Monark',
            slug: 'monark',
        );

        expect($event->projectId)->toBe('proj-123')
            ->and($event->name)->toBe('Monark')
            ->and($event->slug)->toBe('monark');
    });
});

describe('ProjectDeleted', function () {
    it('holds event data', function () {
        $event = new ProjectDeleted(
            projectId: 'proj-456',
        );

        expect($event->projectId)->toBe('proj-456');
    });
});

describe('ProjectMetadataSyncedEvent', function () {
    it('holds event data', function () {
        $event = new ProjectMetadataSyncedEvent(
            projectId: 'proj-789',
            changedFields: ['name', 'description', 'defaultBranch'],
        );

        expect($event->projectId)->toBe('proj-789')
            ->and($event->changedFields)->toBe(['name', 'description', 'defaultBranch']);
    });

    it('accepts empty changed fields', function () {
        $event = new ProjectMetadataSyncedEvent(
            projectId: 'proj-000',
            changedFields: [],
        );

        expect($event->changedFields)->toBe([]);
    });
});

describe('ProjectSyncCompletedEvent', function () {
    it('holds event data', function () {
        $event = new ProjectSyncCompletedEvent(
            projectId: 'proj-101',
            syncJobId: 'job-202',
        );

        expect($event->projectId)->toBe('proj-101')
            ->and($event->syncJobId)->toBe('job-202');
    });
});

describe('ProjectUpdated', function () {
    it('holds event data', function () {
        $event = new ProjectUpdated(
            projectId: 'proj-303',
            name: 'Monark v2',
        );

        expect($event->projectId)->toBe('proj-303')
            ->and($event->name)->toBe('Monark v2');
    });
});
