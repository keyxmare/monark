<?php

declare(strict_types=1);

use App\Catalog\Infrastructure\Scanner\Detector\PythonDetector;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;

describe('PythonDetector', function () {
    it('supports requirements.txt and pyproject.toml', function () {
        $detector = new PythonDetector();
        expect($detector->supportedManifests())->toBe(['requirements.txt', 'pyproject.toml']);
    });

    it('detects Django from requirements.txt', function () {
        $requirements = "django==4.2.0\ncelery>=5.3.0\n";

        $detector = new PythonDetector();
        $stacks = $detector->detect(['requirements.txt' => $requirements]);

        expect($stacks)->toHaveCount(1);
        expect($stacks[0]->language)->toBe('Python');
        expect($stacks[0]->framework)->toBe('Django');
    });

    it('detects Flask from requirements.txt', function () {
        $detector = new PythonDetector();
        $stacks = $detector->detect(['requirements.txt' => "flask~=3.0\n"]);

        expect($stacks[0]->framework)->toBe('Flask');
    });

    it('detects Python without framework', function () {
        $detector = new PythonDetector();
        $stacks = $detector->detect(['requirements.txt' => "requests>=2.28.0\n"]);

        expect($stacks[0]->language)->toBe('Python');
        expect($stacks[0]->framework)->toBe('none');
    });

    it('detects FastAPI from pyproject.toml', function () {
        $pyproject = "[project]\nname = \"my-app\"\nrequires-python = \">=3.12\"\ndependencies = [\"fastapi\"]";

        $detector = new PythonDetector();
        $stacks = $detector->detect(['pyproject.toml' => $pyproject]);

        expect($stacks[0]->language)->toBe('Python');
        expect($stacks[0]->framework)->toBe('FastAPI');
        expect($stacks[0]->version)->toBe('>=3.12');
    });

    it('extracts pip dependencies', function () {
        $requirements = "django==4.2.0\ncelery>=5.3.0\n# comment\nredis\n";

        $detector = new PythonDetector();
        $deps = $detector->extractDependencies(['requirements.txt' => $requirements]);

        expect($deps)->toHaveCount(3);
        expect($deps[0]->name)->toBe('django');
        expect($deps[0]->currentVersion)->toBe('4.2.0');
        expect($deps[0]->packageManager)->toBe(PackageManager::Pip);
        expect($deps[0]->type)->toBe(DependencyType::Runtime);
        expect($deps[2]->name)->toBe('redis');
        expect($deps[2]->currentVersion)->toBe('*');
        expect($deps[0]->repositoryUrl)->toContain('pypi.org/project/django');
    });

    it('returns empty when no manifest', function () {
        $detector = new PythonDetector();
        expect($detector->detect([]))->toBe([]);
    });
});
