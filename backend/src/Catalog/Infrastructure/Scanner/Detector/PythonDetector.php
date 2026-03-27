<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Scanner\Detector;

use App\Catalog\Domain\Port\StackDetectorInterface;
use App\Catalog\Infrastructure\Scanner\VersionHelper;
use App\Shared\Domain\DTO\DetectedDependency;
use App\Shared\Domain\DTO\DetectedStack;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('catalog.stack_detector')]
class PythonDetector implements StackDetectorInterface
{
    public function supportedManifests(): array
    {
        return ['requirements.txt', 'pyproject.toml'];
    }

    public function detect(array $manifestContents): array
    {
        $requirements = $manifestContents['requirements.txt'] ?? null;
        if ($requirements !== null) {
            return [$this->detectFromRequirements($requirements)];
        }

        $pyproject = $manifestContents['pyproject.toml'] ?? null;
        if ($pyproject !== null) {
            return [$this->detectFromPyproject($pyproject)];
        }

        return [];
    }

    public function extractDependencies(array $manifestContents): array
    {
        $requirements = $manifestContents['requirements.txt'] ?? null;
        if ($requirements === null) {
            return [];
        }

        $deps = [];
        $lines = \explode("\n", $requirements);

        foreach ($lines as $line) {
            $line = \trim($line);
            if ($line === '' || \str_starts_with($line, '#') || \str_starts_with($line, '-')) {
                continue;
            }

            if (\preg_match('/^([a-zA-Z0-9_.-]+)\s*(?:[=<>!~]+\s*(.+))?$/', $line, $m)) {
                $deps[] = new DetectedDependency(
                    name: $m[1],
                    currentVersion: isset($m[2]) ? \trim($m[2]) : '*',
                    packageManager: PackageManager::Pip,
                    type: DependencyType::Runtime,
                    repositoryUrl: \sprintf('https://pypi.org/project/%s/', $m[1]),
                );
            }
        }

        return $deps;
    }

    public function enrichStackVersions(array $stacks, array $lockVersions): array
    {
        return $stacks;
    }

    public function enrichDependencyVersions(array $dependencies, array $lockVersions): array
    {
        return $dependencies;
    }

    private function detectFromRequirements(string $content): DetectedStack
    {
        $framework = 'none';
        $lower = \strtolower($content);

        if (\str_contains($lower, 'django')) {
            $framework = 'Django';
        } elseif (\str_contains($lower, 'fastapi')) {
            $framework = 'FastAPI';
        } elseif (\str_contains($lower, 'flask')) {
            $framework = 'Flask';
        }

        return new DetectedStack(language: 'Python', framework: $framework, version: '', frameworkVersion: '');
    }

    private function detectFromPyproject(string $content): DetectedStack
    {
        $framework = 'none';
        $lower = \strtolower($content);

        if (\str_contains($lower, 'django')) {
            $framework = 'Django';
        } elseif (\str_contains($lower, 'fastapi')) {
            $framework = 'FastAPI';
        } elseif (\str_contains($lower, 'flask')) {
            $framework = 'Flask';
        }

        $version = '';
        if (\preg_match('/requires-python\s*=\s*"([^"]+)"/', $content, $m)) {
            $version = $m[1];
        }

        return new DetectedStack(language: 'Python', framework: $framework, version: $version, frameworkVersion: '');
    }
}
