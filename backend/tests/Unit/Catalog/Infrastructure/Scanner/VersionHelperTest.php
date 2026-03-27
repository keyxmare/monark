<?php

declare(strict_types=1);

use App\Catalog\Infrastructure\Scanner\VersionHelper;

describe('VersionHelper', function () {
    it('cleans version prefixes', function () {
        expect(VersionHelper::clean('^8.4'))->toBe('8.4');
        expect(VersionHelper::clean('~5.7.0'))->toBe('5.7.0');
        expect(VersionHelper::clean('>=3.12'))->toBe('3.12');
        expect(VersionHelper::clean('<2.0'))->toBe('2.0');
        expect(VersionHelper::clean('  !1.0 '))->toBe('1.0');
        expect(VersionHelper::clean(''))->toBe('');
    });

    it('parses pnpm lock content', function () {
        $content = <<<'YAML'
dependencies:
  vue:
    specifier: ^3.5.0
    version: 3.5.13

devDependencies:
  typescript:
    specifier: ^5.7.0
    version: 5.7.3
YAML;
        $versions = VersionHelper::parsePnpmLock($content);
        expect($versions)->toHaveKey('vue');
        expect($versions['vue'])->toBe('3.5.13');
    });

    it('parses npm lock content', function () {
        $data = [
            'packages' => [
                '' => ['name' => 'my-app'],
                'node_modules/vue' => ['version' => '3.5.13'],
                'node_modules/@angular/core' => ['version' => '17.3.0'],
            ],
        ];
        $versions = VersionHelper::parseNpmLock($data);
        expect($versions['vue'])->toBe('3.5.13');
        expect($versions['@angular/core'])->toBe('17.3.0');
    });

    it('parses yarn lock content', function () {
        $content = <<<'YARN'
"vue@^3.5.0":
  version: "3.5.13"
  resolved "https://registry.yarnpkg.com/vue/-/vue-3.5.13.tgz"
YARN;
        $versions = VersionHelper::parseYarnLock($content);
        expect($versions)->toHaveKey('vue');
        expect($versions['vue'])->toBe('3.5.13');
    });

    it('parses composer lock packages', function () {
        $lockData = [
            'packages' => [
                ['name' => 'symfony/framework-bundle', 'version' => 'v8.0.3'],
            ],
            'packages-dev' => [
                ['name' => 'pestphp/pest', 'version' => 'v4.0.1'],
            ],
        ];
        $versions = VersionHelper::parseComposerLock($lockData);
        expect($versions['symfony/framework-bundle'])->toBe('8.0.3');
        expect($versions['pestphp/pest'])->toBe('4.0.1');
    });

    it('parses composer lock URLs', function () {
        $lockData = [
            'packages' => [
                ['name' => 'monolog/monolog', 'version' => '3.8.0', 'source' => ['url' => 'https://github.com/Seldaek/monolog.git']],
            ],
            'packages-dev' => [],
        ];
        $urls = VersionHelper::parseComposerLockUrls($lockData);
        expect($urls['monolog/monolog'])->toBe('https://github.com/Seldaek/monolog');
    });

    it('extracts composer platform PHP version', function () {
        $lockData = ['platform' => ['php' => '8.4.2']];
        expect(VersionHelper::extractComposerPhpVersion($lockData))->toBe('8.4.2');
    });

    it('prefers platform for PHP version over nothing', function () {
        $lockData = ['platform' => ['php' => '8.3.0']];
        expect(VersionHelper::extractComposerPhpVersion($lockData))->toBe('8.3.0');
    });

    it('returns null when no PHP platform version', function () {
        expect(VersionHelper::extractComposerPhpVersion([]))->toBeNull();
    });
});
