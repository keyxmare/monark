<?php

declare(strict_types=1);

use App\Identity\Domain\Model\TokenProvider;

describe('TokenProvider', function () {
    it('has exactly two cases', function () {
        expect(TokenProvider::cases())->toHaveCount(2);
    });

    it('has Gitlab case with value gitlab', function () {
        expect(TokenProvider::Gitlab->value)->toBe('gitlab');
    });

    it('has Github case with value github', function () {
        expect(TokenProvider::Github->value)->toBe('github');
    });

    it('creates from valid string via from()', function () {
        expect(TokenProvider::from('gitlab'))->toBe(TokenProvider::Gitlab);
        expect(TokenProvider::from('github'))->toBe(TokenProvider::Github);
    });

    it('throws on invalid string via from()', function () {
        expect(fn () => TokenProvider::from('bitbucket'))->toThrow(\ValueError::class);
    });

    it('returns null on invalid string via tryFrom()', function () {
        expect(TokenProvider::tryFrom('bitbucket'))->toBeNull();
    });

    it('returns case on valid string via tryFrom()', function () {
        expect(TokenProvider::tryFrom('gitlab'))->toBe(TokenProvider::Gitlab);
        expect(TokenProvider::tryFrom('github'))->toBe(TokenProvider::Github);
    });
});
