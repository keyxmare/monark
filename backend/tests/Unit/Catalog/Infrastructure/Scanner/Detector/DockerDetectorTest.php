<?php

declare(strict_types=1);

use App\Catalog\Infrastructure\Scanner\Detector\DockerDetector;

describe('DockerDetector', function () {
    it('supports Dockerfile manifest', function () {
        $detector = new DockerDetector();
        expect($detector->supportedManifests())->toBe(['Dockerfile']);
    });

    it('detects PHP from Dockerfile', function () {
        $dockerfile = "FROM php:8.4-fpm\nRUN apt-get update";

        $detector = new DockerDetector();
        $stacks = $detector->detect(['Dockerfile' => $dockerfile]);

        expect($stacks)->toHaveCount(1);
        expect($stacks[0]->language)->toBe('PHP');
        expect($stacks[0]->framework)->toBe('none');
        expect($stacks[0]->version)->toBe('8.4');
    });

    it('detects Node.js from Dockerfile', function () {
        $dockerfile = "FROM node:22-alpine\nRUN npm install";

        $detector = new DockerDetector();
        $stacks = $detector->detect(['Dockerfile' => $dockerfile]);

        expect($stacks[0]->language)->toBe('Node.js');
        expect($stacks[0]->version)->toBe('22');
    });

    it('ignores unknown base images', function () {
        $dockerfile = "FROM nginx:alpine\nEXPOSE 80";

        $detector = new DockerDetector();
        expect($detector->detect(['Dockerfile' => $dockerfile]))->toBe([]);
    });

    it('returns empty when no Dockerfile', function () {
        $detector = new DockerDetector();
        expect($detector->detect([]))->toBe([]);
    });
});
