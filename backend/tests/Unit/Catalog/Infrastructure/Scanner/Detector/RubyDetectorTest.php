<?php

declare(strict_types=1);

use App\Catalog\Infrastructure\Scanner\Detector\RubyDetector;

describe('RubyDetector', function () {
    it('supports Gemfile manifest', function () {
        $detector = new RubyDetector();
        expect($detector->supportedManifests())->toBe(['Gemfile']);
    });

    it('detects Ruby + Rails', function () {
        $gemfile = "source 'https://rubygems.org'\nruby '3.3.0'\ngem 'rails', '~> 7.1'";

        $detector = new RubyDetector();
        $stacks = $detector->detect(['Gemfile' => $gemfile]);

        expect($stacks)->toHaveCount(1);
        expect($stacks[0]->language)->toBe('Ruby');
        expect($stacks[0]->framework)->toBe('Rails');
        expect($stacks[0]->version)->toBe('3.3.0');
    });

    it('detects Ruby + Sinatra', function () {
        $gemfile = "source 'https://rubygems.org'\ngem 'sinatra'";

        $detector = new RubyDetector();
        $stacks = $detector->detect(['Gemfile' => $gemfile]);

        expect($stacks[0]->framework)->toBe('Sinatra');
    });

    it('returns empty when no Gemfile', function () {
        $detector = new RubyDetector();
        expect($detector->detect([]))->toBe([]);
    });
});
