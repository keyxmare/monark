<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Infrastructure\Scanner\Detector;

use App\Hub\Shared\Domain\DTO\DetectedStack;
use App\Monitoring\Catalog\Domain\Port\StackDetectorInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('catalog.stack_detector')]
class GoDetector implements StackDetectorInterface
{
    public function supportedManifests(): array
    {
        return ['go.mod'];
    }

    public function detect(array $manifestContents): array
    {
        $goMod = $manifestContents['go.mod'] ?? null;
        if ($goMod === null) {
            return [];
        }

        $version = '';
        if (\preg_match('/^go\s+([\d.]+)/m', $goMod, $m)) {
            $version = $m[1];
        }

        $framework = 'none';
        if (\str_contains($goMod, 'github.com/gin-gonic/gin')) {
            $framework = 'Gin';
        } elseif (\str_contains($goMod, 'github.com/gofiber/fiber')) {
            $framework = 'Fiber';
        } elseif (\str_contains($goMod, 'github.com/labstack/echo')) {
            $framework = 'Echo';
        }

        return [new DetectedStack(language: 'Go', framework: $framework, version: $version, frameworkVersion: '')];
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
