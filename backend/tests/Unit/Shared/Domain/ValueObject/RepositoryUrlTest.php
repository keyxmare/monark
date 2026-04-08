<?php

declare(strict_types=1);

use App\Shared\Domain\ValueObject\RepositoryUrl;

describe('RepositoryUrl', function () {
    it('creates with a valid URL', function () {
        $url = new RepositoryUrl('https://gitlab.com/team/monark');

        expect($url->value())->toBe('https://gitlab.com/team/monark');
    });

    it('throws on invalid URL', function () {
        new RepositoryUrl('not-a-url');
    })->throws(\InvalidArgumentException::class);

    it('throws on empty string', function () {
        new RepositoryUrl('');
    })->throws(\InvalidArgumentException::class);

    it('converts to string', function () {
        $url = new RepositoryUrl('https://github.com/org/repo');

        expect((string) $url)->toBe('https://github.com/org/repo');
    });

    it('compares equality', function () {
        $a = new RepositoryUrl('https://gitlab.com/team/monark');
        $b = new RepositoryUrl('https://gitlab.com/team/monark');
        $c = new RepositoryUrl('https://github.com/other/repo');

        expect($a->equals($b))->toBeTrue();
        expect($a->equals($c))->toBeFalse();
    });
});
