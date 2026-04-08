<?php

declare(strict_types=1);

use App\Catalog\Infrastructure\Scanner\Detector\GoDetector;

describe('GoDetector', function () {
    it('supports go.mod manifest', function () {
        $detector = new GoDetector();
        expect($detector->supportedManifests())->toBe(['go.mod']);
    });

    it('detects Go + Gin', function () {
        $goMod = "module github.com/example/app\n\ngo 1.22\n\nrequire (\n\tgithub.com/gin-gonic/gin v1.10.0\n)";

        $detector = new GoDetector();
        $stacks = $detector->detect(['go.mod' => $goMod]);

        expect($stacks)->toHaveCount(1);
        expect($stacks[0]->language)->toBe('Go');
        expect($stacks[0]->framework)->toBe('Gin');
        expect($stacks[0]->version)->toBe('1.22');
    });

    it('detects Go + Fiber', function () {
        $goMod = "module app\n\ngo 1.22\n\nrequire github.com/gofiber/fiber/v2 v2.52.0\n";

        $detector = new GoDetector();
        $stacks = $detector->detect(['go.mod' => $goMod]);

        expect($stacks[0]->framework)->toBe('Fiber');
    });

    it('detects Go without framework', function () {
        $goMod = "module example.com/app\n\ngo 1.21\n\nrequire golang.org/x/text v0.14.0\n";

        $detector = new GoDetector();
        $stacks = $detector->detect(['go.mod' => $goMod]);

        expect($stacks[0]->framework)->toBe('none');
        expect($stacks[0]->version)->toBe('1.21');
    });

    it('returns empty when no go.mod', function () {
        $detector = new GoDetector();
        expect($detector->detect([]))->toBe([]);
    });
});
