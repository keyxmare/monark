<?php

declare(strict_types=1);

use App\Shared\Domain\DTO\DependencyReadDTO;
use App\Shared\Domain\DTO\DetectedDependency;
use App\Shared\Domain\DTO\DetectedStack;
use App\Shared\Domain\DTO\ScanResult;
use App\Shared\Domain\DTO\VulnerabilityReadDTO;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;

describe('DependencyReadDTO', function () {
    it('stores all properties', function () {
        $vuln = new VulnerabilityReadDTO('CVE-2024-001', 'high', 'XSS', 'Cross-site scripting', '2.0.0', 'open');
        $dto = new DependencyReadDTO('symfony/http-kernel', '6.3.0', '7.0.0', 'composer', true, [$vuln]);

        expect($dto->name)->toBe('symfony/http-kernel');
        expect($dto->currentVersion)->toBe('6.3.0');
        expect($dto->latestVersion)->toBe('7.0.0');
        expect($dto->packageManager)->toBe('composer');
        expect($dto->isOutdated)->toBeTrue();
        expect($dto->vulnerabilities)->toHaveCount(1);
        expect($dto->vulnerabilities[0])->toBe($vuln);
    });

    it('defaults vulnerabilities to empty array', function () {
        $dto = new DependencyReadDTO('lodash', '4.17.0', '4.17.21', 'npm', true);

        expect($dto->vulnerabilities)->toBe([]);
    });
});

describe('DetectedDependency', function () {
    it('stores all properties', function () {
        $dto = new DetectedDependency(
            'symfony/console',
            '6.4.0',
            PackageManager::Composer,
            DependencyType::Runtime,
            'https://github.com/symfony/console',
        );

        expect($dto->name)->toBe('symfony/console');
        expect($dto->currentVersion)->toBe('6.4.0');
        expect($dto->packageManager)->toBe(PackageManager::Composer);
        expect($dto->type)->toBe(DependencyType::Runtime);
        expect($dto->repositoryUrl)->toBe('https://github.com/symfony/console');
    });

    it('defaults repositoryUrl to null', function () {
        $dto = new DetectedDependency('lodash', '4.17.0', PackageManager::Npm, DependencyType::Dev);

        expect($dto->repositoryUrl)->toBeNull();
    });
});

describe('DetectedStack', function () {
    it('stores all properties', function () {
        $dto = new DetectedStack('PHP', 'Symfony', '8.4', '7.0');

        expect($dto->language)->toBe('PHP');
        expect($dto->framework)->toBe('Symfony');
        expect($dto->version)->toBe('8.4');
        expect($dto->frameworkVersion)->toBe('7.0');
    });
});

describe('VulnerabilityReadDTO', function () {
    it('stores all properties', function () {
        $dto = new VulnerabilityReadDTO('CVE-2024-999', 'critical', 'RCE', 'Remote code execution', '3.0.0', 'patched');

        expect($dto->cveId)->toBe('CVE-2024-999');
        expect($dto->severity)->toBe('critical');
        expect($dto->title)->toBe('RCE');
        expect($dto->description)->toBe('Remote code execution');
        expect($dto->patchedVersion)->toBe('3.0.0');
        expect($dto->status)->toBe('patched');
    });
});

describe('ScanResult', function () {
    it('stores stacks and dependencies', function () {
        $stack = new DetectedStack('PHP', 'Symfony', '8.4', '7.0');
        $dep = new DetectedDependency('symfony/console', '6.4.0', PackageManager::Composer, DependencyType::Runtime);
        $result = new ScanResult([$stack], [$dep]);

        expect($result->stacks)->toHaveCount(1);
        expect($result->stacks[0])->toBe($stack);
        expect($result->dependencies)->toHaveCount(1);
        expect($result->dependencies[0])->toBe($dep);
    });

    it('accepts empty arrays', function () {
        $result = new ScanResult([], []);

        expect($result->stacks)->toBe([]);
        expect($result->dependencies)->toBe([]);
    });
});
