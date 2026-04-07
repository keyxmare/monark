<?php

declare(strict_types=1);

use App\History\Domain\Model\GapType;
use App\History\Domain\Service\DebtScoreCalculator;

describe('DebtScoreCalculator', function () {
    it('returns Unknown when latest is null', function () {
        $calc = new DebtScoreCalculator();
        expect($calc->determineGapType('1.0.0', null))->toBe(GapType::Unknown);
    });

    it('returns None when current equals latest', function () {
        $calc = new DebtScoreCalculator();
        expect($calc->determineGapType('1.0.0', '1.0.0'))->toBe(GapType::None);
    });

    it('returns Major when major version differs', function () {
        $calc = new DebtScoreCalculator();
        expect($calc->determineGapType('1.2.3', '2.0.0'))->toBe(GapType::Major);
    });

    it('returns Minor when minor version differs', function () {
        $calc = new DebtScoreCalculator();
        expect($calc->determineGapType('1.2.3', '1.5.0'))->toBe(GapType::Minor);
    });

    it('returns Patch when only patch differs', function () {
        $calc = new DebtScoreCalculator();
        expect($calc->determineGapType('1.2.3', '1.2.9'))->toBe(GapType::Patch);
    });

    it('returns Unknown for unparseable versions', function () {
        $calc = new DebtScoreCalculator();
        expect($calc->determineGapType('not-a-version', '1.0.0'))->toBe(GapType::Unknown);
    });

    it('returns 0 for an empty project', function () {
        $calc = new DebtScoreCalculator();
        expect($calc->score(0, 0, 0, 0, 0, 0))->toBe(0.0);
    });

    it('weights majors and vulnerabilities heavier than patches', function () {
        $calc = new DebtScoreCalculator();
        $score = $calc->score(totalDeps: 10, major: 1, minor: 1, patch: 1, vulnerable: 1, ltsGap: 1);
        expect($score)->toBe(\round((5.0 + 2.0 + 0.5 + 8.0 + 3.0) / 10, 2));
    });
});
