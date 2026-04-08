<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\ProjectVisibility;

describe('ProjectVisibility', function () {
    it('has all expected cases', function () {
        $cases = ProjectVisibility::cases();

        expect($cases)->toHaveCount(2);
        expect(ProjectVisibility::Public->value)->toBe('public');
        expect(ProjectVisibility::Private->value)->toBe('private');
    });

    it('creates from valid string', function () {
        expect(ProjectVisibility::from('public'))->toBe(ProjectVisibility::Public);
        expect(ProjectVisibility::from('private'))->toBe(ProjectVisibility::Private);
    });

    it('returns null for invalid string with tryFrom', function () {
        expect(ProjectVisibility::tryFrom('invalid'))->toBeNull();
    });
});
