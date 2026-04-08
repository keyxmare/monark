<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Scanner\Detector;

use App\Catalog\Domain\Port\StackDetectorInterface;
use App\Shared\Domain\DTO\DetectedStack;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('catalog.stack_detector')]
class DockerDetector implements StackDetectorInterface
{
    private const array LANGUAGE_MAP = [
        'php' => 'PHP',
        'node' => 'Node.js',
        'python' => 'Python',
        'golang' => 'Go',
        'rust' => 'Rust',
        'ruby' => 'Ruby',
        'openjdk' => 'Java',
        'eclipse-temurin' => 'Java',
        'amazoncorretto' => 'Java',
        'dotnet' => 'C#',
    ];

    public function supportedManifests(): array
    {
        return ['Dockerfile'];
    }

    public function detect(array $manifestContents): array
    {
        $dockerfile = $manifestContents['Dockerfile'] ?? null;
        if ($dockerfile === null) {
            return [];
        }

        if (!\preg_match('/^FROM\s+([^\s]+)/im', $dockerfile, $m)) {
            return [];
        }

        $image = \strtolower(\explode(':', $m[1])[0]);
        $image = \explode('/', $image);
        $base = \end($image);

        $language = self::LANGUAGE_MAP[$base] ?? null;
        if ($language === null) {
            return [];
        }

        $version = '';
        if (\preg_match('/^FROM\s+[^:]+:(\d+(?:\.\d+)*)/im', $dockerfile, $vm)) {
            $version = $vm[1];
        }

        return [new DetectedStack(language: $language, framework: 'none', version: $version, frameworkVersion: '')];
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
