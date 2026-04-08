<?php

declare(strict_types=1);

use App\Catalog\Infrastructure\Scanner\Detector\PythonDetector;
use App\Shared\Domain\DTO\DetectedDependency;
use App\Shared\Domain\DTO\DetectedStack;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;

describe('PythonDetector', function () {
    describe('supportedManifests', function () {
        it('returns exactly requirements.txt and pyproject.toml', function () {
            $detector = new PythonDetector();
            expect($detector->supportedManifests())->toBe(['requirements.txt', 'pyproject.toml']);
        });
    });

    describe('detect', function () {
        it('detects Django from requirements.txt', function () {
            $detector = new PythonDetector();
            $stacks = $detector->detect(['requirements.txt' => "django==4.2.0\ncelery>=5.3.0\n"]);

            expect($stacks)->toHaveCount(1);
            expect($stacks[0]->language)->toBe('Python');
            expect($stacks[0]->framework)->toBe('Django');
            expect($stacks[0]->version)->toBe('');
            expect($stacks[0]->frameworkVersion)->toBe('');
        });

        it('detects FastAPI from requirements.txt', function () {
            $detector = new PythonDetector();
            $stacks = $detector->detect(['requirements.txt' => "fastapi>=0.100\nuvicorn\n"]);

            expect($stacks[0]->framework)->toBe('FastAPI');
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
            expect($stacks[0]->version)->toBe('');
            expect($stacks[0]->frameworkVersion)->toBe('');
        });

        it('detects Django from pyproject.toml', function () {
            $detector = new PythonDetector();
            $stacks = $detector->detect(['pyproject.toml' => "[project]\nname = \"x\"\ndependencies = [\"django\"]\n"]);

            expect($stacks[0]->framework)->toBe('Django');
            expect($stacks[0]->language)->toBe('Python');
        });

        it('detects FastAPI from pyproject.toml', function () {
            $detector = new PythonDetector();
            $stacks = $detector->detect(['pyproject.toml' => "[project]\ndependencies = [\"fastapi\"]\n"]);

            expect($stacks[0]->framework)->toBe('FastAPI');
        });

        it('detects Flask from pyproject.toml', function () {
            $detector = new PythonDetector();
            $stacks = $detector->detect(['pyproject.toml' => "[project]\ndependencies = [\"flask\"]\n"]);

            expect($stacks[0]->framework)->toBe('Flask');
        });

        it('detects Python without framework from pyproject.toml', function () {
            $detector = new PythonDetector();
            $stacks = $detector->detect(['pyproject.toml' => "[project]\nname = \"x\"\ndependencies = [\"requests\"]\n"]);

            expect($stacks[0]->framework)->toBe('none');
        });

        it('extracts requires-python from pyproject.toml', function () {
            $pyproject = "[project]\nname = \"my-app\"\nrequires-python = \">=3.12\"\ndependencies = [\"fastapi\"]";

            $detector = new PythonDetector();
            $stacks = $detector->detect(['pyproject.toml' => $pyproject]);

            expect($stacks[0]->version)->toBe('>=3.12');
        });

        it('returns empty version when pyproject.toml has no requires-python', function () {
            $pyproject = "[project]\nname = \"my-app\"\ndependencies = [\"requests\"]\n";

            $detector = new PythonDetector();
            $stacks = $detector->detect(['pyproject.toml' => $pyproject]);

            expect($stacks[0]->version)->toBe('');
        });

        it('prefers requirements.txt over pyproject.toml', function () {
            $detector = new PythonDetector();
            $stacks = $detector->detect([
                'requirements.txt' => "django==4.2\n",
                'pyproject.toml' => "[project]\nrequires-python = \">=3.12\"\ndependencies = [\"flask\"]\n",
            ]);

            expect($stacks)->toHaveCount(1);
            expect($stacks[0]->framework)->toBe('Django');
        });

        it('returns empty when no manifest provided', function () {
            $detector = new PythonDetector();
            expect($detector->detect([]))->toBe([]);
        });

        it('framework detection is case-insensitive', function () {
            $detector = new PythonDetector();
            $stacks = $detector->detect(['requirements.txt' => "Django==4.2.0\n"]);
            expect($stacks[0]->framework)->toBe('Django');
        });

        it('detects Django first in priority order over FastAPI', function () {
            $detector = new PythonDetector();
            $stacks = $detector->detect(['requirements.txt' => "django==4.2.0\nfastapi>=0.100\n"]);
            expect($stacks[0]->framework)->toBe('Django');
        });

        it('detects FastAPI before Flask in priority order', function () {
            $detector = new PythonDetector();
            $stacks = $detector->detect(['requirements.txt' => "fastapi>=0.100\nflask~=3.0\n"]);
            expect($stacks[0]->framework)->toBe('FastAPI');
        });
    });

    describe('extractDependencies', function () {
        it('extracts pip dependencies with versions', function () {
            $requirements = "django==4.2.0\ncelery>=5.3.0\nredis\n";

            $detector = new PythonDetector();
            $deps = $detector->extractDependencies(['requirements.txt' => $requirements]);

            expect($deps)->toHaveCount(3);
            expect($deps[0]->name)->toBe('django');
            expect($deps[0]->currentVersion)->toBe('4.2.0');
            expect($deps[0]->packageManager)->toBe(PackageManager::Pip);
            expect($deps[0]->type)->toBe(DependencyType::Runtime);
            expect($deps[1]->name)->toBe('celery');
            expect($deps[1]->currentVersion)->toBe('5.3.0');
            expect($deps[2]->name)->toBe('redis');
            expect($deps[2]->currentVersion)->toBe('*');
        });

        it('generates pypi.org repository URLs', function () {
            $detector = new PythonDetector();
            $deps = $detector->extractDependencies(['requirements.txt' => "requests>=2.28\n"]);

            expect($deps[0]->repositoryUrl)->toBe('https://pypi.org/project/requests/');
        });

        it('skips comment lines', function () {
            $detector = new PythonDetector();
            $deps = $detector->extractDependencies(['requirements.txt' => "# this is a comment\ndjango==4.2\n"]);

            expect($deps)->toHaveCount(1);
            expect($deps[0]->name)->toBe('django');
        });

        it('skips lines starting with dash', function () {
            $detector = new PythonDetector();
            $deps = $detector->extractDependencies(['requirements.txt' => "-r base.txt\n-e git+https://...\ndjango==4.2\n"]);

            expect($deps)->toHaveCount(1);
            expect($deps[0]->name)->toBe('django');
        });

        it('skips empty lines', function () {
            $detector = new PythonDetector();
            $deps = $detector->extractDependencies(['requirements.txt' => "django==4.2\n\n\ncelery>=5.3\n"]);

            expect($deps)->toHaveCount(2);
        });

        it('returns empty when no requirements.txt', function () {
            $detector = new PythonDetector();
            expect($detector->extractDependencies([]))->toBe([]);
        });

        it('returns empty when only pyproject.toml is provided', function () {
            $detector = new PythonDetector();
            $deps = $detector->extractDependencies(['pyproject.toml' => "[project]\ndependencies = [\"django\"]\n"]);
            expect($deps)->toBe([]);
        });

        it('handles various version specifiers', function () {
            $requirements = "pkg1~=1.0\npkg2!=2.0\npkg3<3.0\npkg4>1.0\npkg5<=5.0\n";
            $detector = new PythonDetector();
            $deps = $detector->extractDependencies(['requirements.txt' => $requirements]);

            expect($deps)->toHaveCount(5);
            expect($deps[0]->name)->toBe('pkg1');
            expect($deps[0]->currentVersion)->toBe('1.0');
        });

        it('handles packages with dots and underscores in name', function () {
            $detector = new PythonDetector();
            $deps = $detector->extractDependencies(['requirements.txt' => "my_package.lib==1.0\n"]);

            expect($deps)->toHaveCount(1);
            expect($deps[0]->name)->toBe('my_package.lib');
        });
    });

    describe('enrichStackVersions', function () {
        it('returns stacks unchanged (passthrough)', function () {
            $stacks = [new DetectedStack('Python', 'Django', '>=3.12', '')];
            $detector = new PythonDetector();

            $enriched = $detector->enrichStackVersions($stacks, ['django' => '4.2.0']);

            expect($enriched)->toBe($stacks);
            expect($enriched[0]->version)->toBe('>=3.12');
        });

        it('returns empty array for empty input', function () {
            $detector = new PythonDetector();
            expect($detector->enrichStackVersions([], []))->toBe([]);
        });
    });

    describe('enrichDependencyVersions', function () {
        it('returns dependencies unchanged (passthrough)', function () {
            $deps = [new DetectedDependency('django', '4.2.0', PackageManager::Pip, DependencyType::Runtime)];
            $detector = new PythonDetector();

            $enriched = $detector->enrichDependencyVersions($deps, ['django' => '4.2.5']);

            expect($enriched)->toBe($deps);
            expect($enriched[0]->currentVersion)->toBe('4.2.0');
        });

        it('returns empty array for empty input', function () {
            $detector = new PythonDetector();
            expect($detector->enrichDependencyVersions([], []))->toBe([]);
        });
    });
});
