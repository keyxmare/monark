<?php

declare(strict_types=1);

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\NotFoundException;

describe('DomainException', function () {
    it('is abstract and extends PHP DomainException', function () {
        $reflection = new ReflectionClass(DomainException::class);

        expect($reflection->isAbstract())->toBeTrue();
        expect($reflection->getParentClass()->getName())->toBe(\DomainException::class);
    });
});

describe('NotFoundException', function () {
    it('creates from entity with correct message', function () {
        $exception = NotFoundException::forEntity('Project', 'abc-123');

        expect($exception->getMessage())->toBe('Project with id "abc-123" was not found.');
        expect($exception->entity)->toBe('Project');
        expect($exception->entityId)->toBe('abc-123');
    });

    it('extends DomainException', function () {
        $exception = NotFoundException::forEntity('User', '42');

        expect($exception)->toBeInstanceOf(DomainException::class);
    });

    it('is throwable', function () {
        expect(fn () => throw NotFoundException::forEntity('Dependency', 'dep-1'))
            ->toThrow(NotFoundException::class, 'Dependency with id "dep-1" was not found.');
    });
});
