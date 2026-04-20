<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Infrastructure\Scanner\Detector;

use App\Hub\Shared\Domain\DTO\DetectedStack;
use App\Monitoring\Catalog\Domain\Port\StackDetectorInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('catalog.stack_detector')]
class RustDetector implements StackDetectorInterface
{
    public function supportedManifests(): array
    {
        return ['Cargo.toml'];
    }

    public function detect(array $manifestContents): array
    {
        $cargoToml = $manifestContents['Cargo.toml'] ?? null;
        if ($cargoToml === null) {
            return [];
        }

        $version = '';
        if (\preg_match('/\[package\].*?version\s*=\s*"([^"]+)"/s', $cargoToml, $m)) {
            $version = $m[1];
        }

        $framework = 'none';
        if (\str_contains($cargoToml, 'actix-web')) {
            $framework = 'Actix';
        } elseif (\str_contains($cargoToml, 'axum')) {
            $framework = 'Axum';
        } elseif (\str_contains($cargoToml, 'rocket')) {
            $framework = 'Rocket';
        }

        return [new DetectedStack(language: 'Rust', framework: $framework, version: $version, frameworkVersion: '')];
    }

    public function extractDependencies(array $manifestContents): array
    {
        return [];
    }

    public function enrichStackVersions(array $stacks, array $lockVersions): array
    {
        return $stacks;
    }

    public function enrichDependencyVersions(array $dependencies, array $lockVersions): array
    {
        return $dependencies;
    }
}
