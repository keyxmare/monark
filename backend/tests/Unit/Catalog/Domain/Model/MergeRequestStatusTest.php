<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\MergeRequestStatus;

describe('MergeRequestStatus', function () {
    it('has all expected cases', function () {
        $cases = MergeRequestStatus::cases();

        expect($cases)->toHaveCount(4);
        expect(MergeRequestStatus::Open->value)->toBe('open');
        expect(MergeRequestStatus::Merged->value)->toBe('merged');
        expect(MergeRequestStatus::Closed->value)->toBe('closed');
        expect(MergeRequestStatus::Draft->value)->toBe('draft');
    });

    it('creates from valid string', function () {
        expect(MergeRequestStatus::from('open'))->toBe(MergeRequestStatus::Open);
        expect(MergeRequestStatus::from('merged'))->toBe(MergeRequestStatus::Merged);
        expect(MergeRequestStatus::from('closed'))->toBe(MergeRequestStatus::Closed);
        expect(MergeRequestStatus::from('draft'))->toBe(MergeRequestStatus::Draft);
    });

    it('returns null for invalid string with tryFrom', function () {
        expect(MergeRequestStatus::tryFrom('invalid'))->toBeNull();
    });
});
