<?php

declare(strict_types=1);

use App\Catalog\Infrastructure\Scanner\Detector\RustDetector;

describe('RustDetector', function () {
    it('supports Cargo.toml manifest', function () {
        $detector = new RustDetector();
        expect($detector->supportedManifests())->toBe(['Cargo.toml']);
    });

    it('detects Rust + Actix', function () {
        $cargoToml = "[package]\nname = \"my-app\"\nversion = \"0.5.0\"\n\n[dependencies]\nactix-web = \"4\"";

        $detector = new RustDetector();
        $stacks = $detector->detect(['Cargo.toml' => $cargoToml]);

        expect($stacks)->toHaveCount(1);
        expect($stacks[0]->language)->toBe('Rust');
        expect($stacks[0]->framework)->toBe('Actix');
        expect($stacks[0]->version)->toBe('0.5.0');
    });

    it('detects Rust + Axum', function () {
        $cargoToml = "[package]\nname = \"api\"\nversion = \"1.0.0\"\n\n[dependencies]\naxum = \"0.7\"";

        $detector = new RustDetector();
        $stacks = $detector->detect(['Cargo.toml' => $cargoToml]);

        expect($stacks[0]->framework)->toBe('Axum');
    });

    it('returns empty when no Cargo.toml', function () {
        $detector = new RustDetector();
        expect($detector->detect([]))->toBe([]);
    });
});
