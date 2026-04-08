<?php

declare(strict_types=1);

use App\Dependency\Domain\ValueObject\SemanticVersion;

describe('SemanticVersion', function () {
    describe('parse', function () {
        it('parses standard semver', function () {
            $v = SemanticVersion::parse('1.2.3');

            expect($v->major)->toBe(1)
                ->and($v->minor)->toBe(2)
                ->and($v->patch)->toBe(3)
                ->and($v->preRelease)->toBeNull();
        });

        it('parses with v prefix', function () {
            $v = SemanticVersion::parse('v2.0.1');

            expect($v->major)->toBe(2)
                ->and($v->minor)->toBe(0)
                ->and($v->patch)->toBe(1);
        });

        it('parses with pre-release suffix', function () {
            $v = SemanticVersion::parse('1.0.0-beta.1');

            expect($v->preRelease)->toBe('beta.1');
        });

        it('normalizes two-segment versions', function () {
            $v = SemanticVersion::parse('1.2');

            expect($v->patch)->toBe(0);
        });

        it('throws on invalid format', function () {
            SemanticVersion::parse('not-a-version');
        })->throws(\InvalidArgumentException::class);

        it('throws on empty string', function () {
            SemanticVersion::parse('');
        })->throws(\InvalidArgumentException::class);
    });

    describe('isNewerThan', function () {
        it('detects newer major', function () {
            $v1 = SemanticVersion::parse('2.0.0');
            $v2 = SemanticVersion::parse('1.9.9');

            expect($v1->isNewerThan($v2))->toBeTrue();
        });

        it('detects newer minor', function () {
            $v1 = SemanticVersion::parse('1.3.0');
            $v2 = SemanticVersion::parse('1.2.9');

            expect($v1->isNewerThan($v2))->toBeTrue();
        });

        it('detects newer patch', function () {
            $v1 = SemanticVersion::parse('1.0.2');
            $v2 = SemanticVersion::parse('1.0.1');

            expect($v1->isNewerThan($v2))->toBeTrue();
        });

        it('release is newer than pre-release of same version', function () {
            $release = SemanticVersion::parse('1.0.0');
            $preRelease = SemanticVersion::parse('1.0.0-beta.1');

            expect($release->isNewerThan($preRelease))->toBeTrue();
            expect($preRelease->isNewerThan($release))->toBeFalse();
        });

        it('is irreflexive', function () {
            $v = SemanticVersion::parse('1.2.3');

            expect($v->isNewerThan($v))->toBeFalse();
        });

        it('is transitive', function () {
            $a = SemanticVersion::parse('3.0.0');
            $b = SemanticVersion::parse('2.0.0');
            $c = SemanticVersion::parse('1.0.0');

            expect($a->isNewerThan($b))->toBeTrue()
                ->and($b->isNewerThan($c))->toBeTrue()
                ->and($a->isNewerThan($c))->toBeTrue();
        });

        it('compares pre-release segments numerically', function () {
            $v1 = SemanticVersion::parse('1.0.0-alpha.10');
            $v2 = SemanticVersion::parse('1.0.0-alpha.2');

            expect($v1->isNewerThan($v2))->toBeTrue();
        });

        it('orders pre-release alphabetically when not numeric', function () {
            $beta = SemanticVersion::parse('1.0.0-beta');
            $alpha = SemanticVersion::parse('1.0.0-alpha');

            expect($beta->isNewerThan($alpha))->toBeTrue();
        });
    });

    describe('isCompatibleWith', function () {
        it('same major is compatible', function () {
            $v1 = SemanticVersion::parse('1.2.3');
            $v2 = SemanticVersion::parse('1.9.0');

            expect($v1->isCompatibleWith($v2))->toBeTrue();
        });

        it('different major is incompatible', function () {
            $v1 = SemanticVersion::parse('1.0.0');
            $v2 = SemanticVersion::parse('2.0.0');

            expect($v1->isCompatibleWith($v2))->toBeFalse();
        });
    });

    describe('gaps', function () {
        it('calculates major gap', function () {
            $current = SemanticVersion::parse('1.0.0');
            $latest = SemanticVersion::parse('3.2.1');

            expect($current->getMajorGap($latest))->toBe(2);
        });

        it('calculates minor gap', function () {
            $current = SemanticVersion::parse('1.0.0');
            $latest = SemanticVersion::parse('1.5.0');

            expect($current->getMinorGap($latest))->toBe(5);
        });

        it('calculates patch gap', function () {
            $current = SemanticVersion::parse('1.0.0');
            $latest = SemanticVersion::parse('1.0.7');

            expect($current->getPatchGap($latest))->toBe(7);
        });
    });

    describe('isPreRelease', function () {
        it('returns true for pre-release', function () {
            expect(SemanticVersion::parse('1.0.0-rc.1')->isPreRelease())->toBeTrue();
        });

        it('returns false for stable', function () {
            expect(SemanticVersion::parse('1.0.0')->isPreRelease())->toBeFalse();
        });
    });

    describe('equality and roundtrip', function () {
        it('equals identical version', function () {
            $v1 = SemanticVersion::parse('1.2.3-beta.1');
            $v2 = SemanticVersion::parse('1.2.3-beta.1');

            expect($v1->equals($v2))->toBeTrue();
        });

        it('roundtrips through string', function () {
            $original = SemanticVersion::parse('1.2.3-beta.1');
            $roundtrip = SemanticVersion::parse((string) $original);

            expect($roundtrip->equals($original))->toBeTrue();
        });

        it('serializes to JSON as string', function () {
            $v = SemanticVersion::parse('1.2.3');

            expect($v->jsonSerialize())->toBe('1.2.3');
        });
    });
});
