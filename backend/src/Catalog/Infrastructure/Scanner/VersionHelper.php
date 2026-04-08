<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Scanner;

final class VersionHelper
{
    public static function clean(string $version): string
    {
        return \ltrim(\trim($version), '^~>=<! ');
    }

    /** @return array<string, string> */
    public static function parsePnpmLock(string $content): array
    {
        $versions = [];

        if (\preg_match_all('/^\s+([\'"]?)([a-z@][a-z0-9\/@._-]*)\1:\s*$/m', $content, $pkgMatches, \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE)) {
            foreach ($pkgMatches as $match) {
                $pkgName = $match[2][0];
                $offset = $match[0][1];
                $rest = \substr($content, $offset, 500);
                if (\preg_match('/version:\s*[\'"]?(\d+(?:\.\d+)*)/m', $rest, $vm)) {
                    $versions[$pkgName] = \ltrim($vm[1], 'v');
                }
            }
        }

        return $versions;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    public static function parseNpmLock(array $data): array
    {
        $versions = [];

        /** @var array<string, array{version?: string}> $packages */
        $packages = \is_array($data['packages'] ?? null) ? $data['packages'] : [];
        foreach ($packages as $path => $info) {
            if ($path === '' || !\str_starts_with($path, 'node_modules/')) {
                continue;
            }
            $name = \substr($path, \strlen('node_modules/'));
            if (isset($info['version'])) {
                $versions[$name] = \ltrim($info['version'], 'v');
            }
        }

        return $versions;
    }

    /** @return array<string, string> */
    public static function parseYarnLock(string $content): array
    {
        $versions = [];

        if (\preg_match_all('/^"?(@?[a-z][a-z0-9\/@._-]*)@(?:npm:)?[^":\n]+(?:,\s*"?@?[a-z][a-z0-9\/@._-]*@(?:npm:)?[^":\n]+)*"?:\s*$/m', $content, $pkgMatches, \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE)) {
            foreach ($pkgMatches as $match) {
                $pkgName = $match[1][0];
                $offset = $match[0][1];
                $rest = \substr($content, $offset, 300);
                if (\preg_match('/^\s+version:\s*["\']?(\d+(?:\.\d+)*)/m', $rest, $vm)) {
                    if (!isset($versions[$pkgName])) {
                        $versions[$pkgName] = $vm[1];
                    }
                }
            }
        }

        return $versions;
    }

    /**
     * @param array<string, mixed> $lockData
     * @return array<string, string>
     */
    public static function parseComposerLock(array $lockData): array
    {
        /** @var list<array{name?: string, version?: string}> $packages */
        $packages = \array_merge(
            \is_array($lockData['packages'] ?? null) ? $lockData['packages'] : [],
            \is_array($lockData['packages-dev'] ?? null) ? $lockData['packages-dev'] : [],
        );

        $versions = [];
        foreach ($packages as $pkg) {
            if (isset($pkg['name'])) {
                $versions[$pkg['name']] = \ltrim($pkg['version'] ?? '', 'v');
            }
        }

        return $versions;
    }

    /**
     * @param array<string, mixed> $lockData
     * @return array<string, string>
     */
    public static function parseComposerLockUrls(array $lockData): array
    {
        /** @var list<array{name?: string, source?: array{url?: string}, homepage?: string}> $packages */
        $packages = \array_merge(
            \is_array($lockData['packages'] ?? null) ? $lockData['packages'] : [],
            \is_array($lockData['packages-dev'] ?? null) ? $lockData['packages-dev'] : [],
        );

        $urls = [];
        foreach ($packages as $pkg) {
            $url = ($pkg['source']['url'] ?? null) ?? ($pkg['homepage'] ?? null);
            if ($url !== null && isset($pkg['name'])) {
                $urls[$pkg['name']] = \rtrim(\str_replace('.git', '', $url), '/');
            }
        }

        return $urls;
    }

    /**
     * @param array<string, mixed> $lockData
     */
    public static function extractComposerPhpVersion(array $lockData): ?string
    {
        /** @var array<string, string>|null $platform */
        $platform = \is_array($lockData['platform'] ?? null) ? $lockData['platform'] : null;
        /** @var array<string, string>|null $platformOverrides */
        $platformOverrides = \is_array($lockData['platform-overrides'] ?? null) ? $lockData['platform-overrides'] : null;

        return $platformOverrides['php'] ?? $platform['php'] ?? null;
    }
}
