<?php

declare(strict_types=1);

use App\Assessment\Domain\Event\QuizCreated;
use App\Assessment\Domain\Event\QuizDeleted;
use App\Assessment\Domain\Event\QuizUpdated;
use App\Catalog\Domain\Event\ProjectCreated;
use App\Catalog\Domain\Event\ProjectDeleted;
use App\Catalog\Domain\Event\ProjectSyncCompletedEvent;
use App\Catalog\Domain\Event\ProjectUpdated;
use App\Dependency\Domain\Event\DependencyCreated;
use App\Dependency\Domain\Event\DependencyDeleted;
use App\Dependency\Domain\Event\DependencyUpdated;

it('creates DependencyCreated event', function () {
    $event = new DependencyCreated('d-1', 'symfony/http-kernel');
    expect($event->dependencyId)->toBe('d-1');
    expect($event->name)->toBe('symfony/http-kernel');
});

it('creates DependencyUpdated event', function () {
    $event = new DependencyUpdated('d-1', 'symfony/http-kernel');
    expect($event->dependencyId)->toBe('d-1');
    expect($event->name)->toBe('symfony/http-kernel');
});

it('creates DependencyDeleted event', function () {
    $event = new DependencyDeleted('d-1', 'symfony/http-kernel');
    expect($event->dependencyId)->toBe('d-1');
    expect($event->name)->toBe('symfony/http-kernel');
});

it('creates ProjectCreated event', function () {
    $event = new ProjectCreated('p-1', 'Monark', 'monark');
    expect($event->projectId)->toBe('p-1');
    expect($event->name)->toBe('Monark');
    expect($event->slug)->toBe('monark');
});

it('creates ProjectUpdated event', function () {
    $event = new ProjectUpdated('p-1', 'Monark');
    expect($event->projectId)->toBe('p-1');
    expect($event->name)->toBe('Monark');
});

it('creates ProjectDeleted event', function () {
    $event = new ProjectDeleted('p-1');
    expect($event->projectId)->toBe('p-1');
});

it('creates ProjectSyncCompletedEvent', function () {
    $event = new ProjectSyncCompletedEvent('p-1', 'sj-1');
    expect($event->projectId)->toBe('p-1');
    expect($event->syncJobId)->toBe('sj-1');
});

it('creates QuizCreated event', function () {
    $event = new QuizCreated('q-1', 'PHP Quiz');
    expect($event->quizId)->toBe('q-1');
    expect($event->title)->toBe('PHP Quiz');
});

it('creates QuizUpdated event', function () {
    $event = new QuizUpdated('q-1', 'PHP Quiz');
    expect($event->quizId)->toBe('q-1');
});

it('creates QuizDeleted event', function () {
    $event = new QuizDeleted('q-1');
    expect($event->quizId)->toBe('q-1');
});
