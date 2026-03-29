<?php

declare(strict_types=1);

use App\Dependency\Domain\DTO\RegistryVersion;

describe('RegistryVersion', function () {
    it('creates with all properties', function () {
        $date = new DateTimeImmutable('2026-06-15');
        $rv = new RegistryVersion(version: '3.5.0', releaseDate: $date, isLatest: true);

        expect($rv->version)->toBe('3.5.0');
        expect($rv->releaseDate)->toBe($date);
        expect($rv->isLatest)->toBeTrue();
    });

    it('creates with defaults', function () {
        $rv = new RegistryVersion(version: '1.0.0');

        expect($rv->version)->toBe('1.0.0');
        expect($rv->releaseDate)->toBeNull();
        expect($rv->isLatest)->toBeFalse();
    });
});
