<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Scanner\Detector;

use App\Catalog\Domain\Port\StackDetectorInterface;
use App\Shared\Domain\DTO\DetectedStack;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('catalog.stack_detector')]
class RubyDetector implements StackDetectorInterface
{
    public function supportedManifests(): array
    {
        return ['Gemfile'];
    }

    public function detect(array $manifestContents): array
    {
        $gemfile = $manifestContents['Gemfile'] ?? null;
        if ($gemfile === null) {
            return [];
        }

        $framework = 'none';
        if (\str_contains($gemfile, "'rails'") || \str_contains($gemfile, '"rails"')) {
            $framework = 'Rails';
        } elseif (\str_contains($gemfile, "'sinatra'") || \str_contains($gemfile, '"sinatra"')) {
            $framework = 'Sinatra';
        }

        $version = '';
        if (\preg_match("/ruby ['\"]([^'\"]+)['\"]/", $gemfile, $m)) {
            $version = $m[1];
        }

        return [new DetectedStack(language: 'Ruby', framework: $framework, version: $version, frameworkVersion: '')];
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
