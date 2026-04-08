<?php

declare(strict_types=1);

use App\Shared\Domain\ValueObject\Slug;

describe('Slug', function () {
    it('creates with a valid slug', function () {
        $slug = new Slug('my-project');

        expect($slug->value())->toBe('my-project');
    });

    it('creates with a single word slug', function () {
        $slug = new Slug('monark');

        expect($slug->value())->toBe('monark');
    });

    it('throws on uppercase characters', function () {
        new Slug('My-Project');
    })->throws(\InvalidArgumentException::class);

    it('throws on spaces', function () {
        new Slug('my project');
    })->throws(\InvalidArgumentException::class);

    it('throws on special characters', function () {
        new Slug('my_project');
    })->throws(\InvalidArgumentException::class);

    it('throws on empty string', function () {
        new Slug('');
    })->throws(\InvalidArgumentException::class);

    it('throws on leading hyphen', function () {
        new Slug('-my-project');
    })->throws(\InvalidArgumentException::class);

    it('throws on trailing hyphen', function () {
        new Slug('my-project-');
    })->throws(\InvalidArgumentException::class);

    it('throws on consecutive hyphens', function () {
        new Slug('my--project');
    })->throws(\InvalidArgumentException::class);

    it('converts to string', function () {
        $slug = new Slug('my-project');

        expect((string) $slug)->toBe('my-project');
    });

    it('compares equality', function () {
        $a = new Slug('my-project');
        $b = new Slug('my-project');
        $c = new Slug('other-project');

        expect($a->equals($b))->toBeTrue();
        expect($a->equals($c))->toBeFalse();
    });
});
