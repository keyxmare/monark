<?php

declare(strict_types=1);

use App\Catalog\Infrastructure\Scanner\VersionHelper;

describe('VersionHelper', function () {
    describe('clean', function () {
        it('strips caret prefix', function () {
            expect(VersionHelper::clean('^8.4'))->toBe('8.4');
        });

        it('strips tilde prefix', function () {
            expect(VersionHelper::clean('~5.7.0'))->toBe('5.7.0');
        });

        it('strips greater-than-or-equal prefix', function () {
            expect(VersionHelper::clean('>=3.12'))->toBe('3.12');
        });

        it('strips less-than prefix', function () {
            expect(VersionHelper::clean('<2.0'))->toBe('2.0');
        });

        it('strips greater-than prefix', function () {
            expect(VersionHelper::clean('>1.0'))->toBe('1.0');
        });

        it('strips equal prefix', function () {
            expect(VersionHelper::clean('=4.0'))->toBe('4.0');
        });

        it('strips ! prefix with surrounding whitespace', function () {
            expect(VersionHelper::clean('  !1.0 '))->toBe('1.0');
        });

        it('strips leading spaces as version prefixes', function () {
            expect(VersionHelper::clean(' 3.0'))->toBe('3.0');
        });

        it('returns empty string for empty input', function () {
            expect(VersionHelper::clean(''))->toBe('');
        });

        it('returns plain version unchanged', function () {
            expect(VersionHelper::clean('1.2.3'))->toBe('1.2.3');
        });

        it('strips multiple prefix characters', function () {
            expect(VersionHelper::clean('>=^~2.0'))->toBe('2.0');
        });
    });

    describe('parsePnpmLock', function () {
        it('parses standard pnpm lock content', function () {
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
            expect($versions)->toHaveKey('typescript');
            expect($versions['typescript'])->toBe('5.7.3');
        });

        it('returns empty array for empty content', function () {
            expect(VersionHelper::parsePnpmLock(''))->toBe([]);
        });

        it('returns empty array for content without matching pattern', function () {
            expect(VersionHelper::parsePnpmLock("lockfileVersion: '9.0'\n"))->toBe([]);
        });

        it('parses multiple packages', function () {
            $content = <<<'YAML'
dependencies:
  alpha:
    specifier: ^1.0
    version: 1.2.3
  beta:
    specifier: ^2.0
    version: 2.0.0
YAML;
            $versions = VersionHelper::parsePnpmLock($content);
            expect($versions)->toHaveKey('alpha');
            expect($versions['alpha'])->toBe('1.2.3');
            expect($versions)->toHaveKey('beta');
            expect($versions['beta'])->toBe('2.0.0');
        });

        it('parses scoped packages', function () {
            $content = <<<'YAML'
dependencies:
  '@vue/reactivity':
    specifier: ^3.5.0
    version: 3.5.13
YAML;
            $versions = VersionHelper::parsePnpmLock($content);
            expect($versions)->toHaveKey('@vue/reactivity');
            expect($versions['@vue/reactivity'])->toBe('3.5.13');
        });

        it('skips packages without version line', function () {
            $content = <<<'YAML'
dependencies:
  noversion:
    specifier: ^1.0
    resolution: "something"
YAML;
            $versions = VersionHelper::parsePnpmLock($content);
            expect($versions)->not->toHaveKey('noversion');
        });
    });

    describe('parseNpmLock', function () {
        it('parses packages from node_modules paths', function () {
            $data = [
                'packages' => [
                    '' => ['name' => 'my-app'],
                    'node_modules/vue' => ['version' => '3.5.13'],
                    'node_modules/@angular/core' => ['version' => '17.3.0'],
                ],
            ];
            $versions = VersionHelper::parseNpmLock($data);
            expect($versions)->toBe([
                'vue' => '3.5.13',
                '@angular/core' => '17.3.0',
            ]);
        });

        it('skips root package entry', function () {
            $data = [
                'packages' => [
                    '' => ['name' => 'my-app', 'version' => '1.0.0'],
                ],
            ];
            $versions = VersionHelper::parseNpmLock($data);
            expect($versions)->toBe([]);
        });

        it('skips entries not starting with node_modules/', function () {
            $data = [
                'packages' => [
                    'lib/something' => ['version' => '1.0.0'],
                ],
            ];
            $versions = VersionHelper::parseNpmLock($data);
            expect($versions)->toBe([]);
        });

        it('strips v prefix from version', function () {
            $data = [
                'packages' => [
                    'node_modules/foo' => ['version' => 'v2.1.0'],
                ],
            ];
            $versions = VersionHelper::parseNpmLock($data);
            expect($versions['foo'])->toBe('2.1.0');
        });

        it('skips entries without version key', function () {
            $data = [
                'packages' => [
                    'node_modules/no-ver' => ['resolved' => 'https://example.com'],
                ],
            ];
            $versions = VersionHelper::parseNpmLock($data);
            expect($versions)->toBe([]);
        });

        it('returns empty when packages key is missing', function () {
            $versions = VersionHelper::parseNpmLock([]);
            expect($versions)->toBe([]);
        });

        it('returns empty when packages is not an array', function () {
            $versions = VersionHelper::parseNpmLock(['packages' => 'invalid']);
            expect($versions)->toBe([]);
        });
    });

    describe('parseYarnLock', function () {
        it('parses standard yarn lock content', function () {
            $content = <<<'YARN'
"vue@^3.5.0":
  version: "3.5.13"
  resolved "https://registry.yarnpkg.com/vue/-/vue-3.5.13.tgz"
YARN;
            $versions = VersionHelper::parseYarnLock($content);
            expect($versions)->toBe(['vue' => '3.5.13']);
        });

        it('returns empty for empty content', function () {
            expect(VersionHelper::parseYarnLock(''))->toBe([]);
        });

        it('returns empty for content without version entries', function () {
            expect(VersionHelper::parseYarnLock("__metadata:\n  version: 8\n"))->toBe([]);
        });

        it('keeps first version for duplicate package entries', function () {
            $content = <<<'YARN'
"vue@^3.4.0":
  version: "3.4.38"
  resolved "https://registry.yarnpkg.com/vue/-/vue-3.4.38.tgz"

"vue@^3.5.0":
  version: "3.5.13"
  resolved "https://registry.yarnpkg.com/vue/-/vue-3.5.13.tgz"
YARN;
            $versions = VersionHelper::parseYarnLock($content);
            expect($versions['vue'])->toBe('3.4.38');
        });

        it('parses scoped packages', function () {
            $content = <<<'YARN'
"@vue/reactivity@npm:^3.5.0":
  version: 3.5.13
  resolution: "@vue/reactivity@npm:3.5.13"
YARN;
            $versions = VersionHelper::parseYarnLock($content);
            expect($versions)->toHaveKey('@vue/reactivity');
            expect($versions['@vue/reactivity'])->toBe('3.5.13');
        });

        it('parses entries without quotes', function () {
            $content = <<<'YARN'
vue@^3.5.0:
  version: "3.5.13"
YARN;
            $versions = VersionHelper::parseYarnLock($content);
            expect($versions)->toHaveKey('vue');
            expect($versions['vue'])->toBe('3.5.13');
        });
    });

    describe('parseComposerLock', function () {
        it('parses both packages and packages-dev', function () {
            $lockData = [
                'packages' => [
                    ['name' => 'symfony/framework-bundle', 'version' => 'v8.0.3'],
                ],
                'packages-dev' => [
                    ['name' => 'pestphp/pest', 'version' => 'v4.0.1'],
                ],
            ];
            $versions = VersionHelper::parseComposerLock($lockData);
            expect($versions)->toBe([
                'symfony/framework-bundle' => '8.0.3',
                'pestphp/pest' => '4.0.1',
            ]);
        });

        it('strips v prefix from versions', function () {
            $versions = VersionHelper::parseComposerLock([
                'packages' => [['name' => 'a/b', 'version' => 'v1.0.0']],
            ]);
            expect($versions['a/b'])->toBe('1.0.0');
        });

        it('handles missing version key as empty string', function () {
            $versions = VersionHelper::parseComposerLock([
                'packages' => [['name' => 'a/b']],
            ]);
            expect($versions['a/b'])->toBe('');
        });

        it('handles missing packages key', function () {
            $versions = VersionHelper::parseComposerLock([]);
            expect($versions)->toBe([]);
        });

        it('handles non-array packages key', function () {
            $versions = VersionHelper::parseComposerLock(['packages' => 'invalid']);
            expect($versions)->toBe([]);
        });

        it('handles missing packages-dev key', function () {
            $versions = VersionHelper::parseComposerLock([
                'packages' => [['name' => 'a/b', 'version' => '1.0.0']],
            ]);
            expect($versions)->toBe(['a/b' => '1.0.0']);
        });

        it('skips entries without name key', function () {
            $versions = VersionHelper::parseComposerLock([
                'packages' => [['version' => '1.0.0']],
            ]);
            expect($versions)->toBe([]);
        });
    });

    describe('parseComposerLockUrls', function () {
        it('extracts URL from source and strips .git suffix', function () {
            $lockData = [
                'packages' => [
                    ['name' => 'monolog/monolog', 'version' => '3.8.0', 'source' => ['url' => 'https://github.com/Seldaek/monolog.git']],
                ],
                'packages-dev' => [],
            ];
            $urls = VersionHelper::parseComposerLockUrls($lockData);
            expect($urls['monolog/monolog'])->toBe('https://github.com/Seldaek/monolog');
        });

        it('falls back to homepage when source url is missing', function () {
            $lockData = [
                'packages' => [
                    ['name' => 'some/lib', 'version' => '1.0.0', 'homepage' => 'https://example.com/some-lib/'],
                ],
                'packages-dev' => [],
            ];
            $urls = VersionHelper::parseComposerLockUrls($lockData);
            expect($urls['some/lib'])->toBe('https://example.com/some-lib');
        });

        it('strips trailing slash from URL', function () {
            $lockData = [
                'packages' => [
                    ['name' => 'a/b', 'source' => ['url' => 'https://github.com/a/b/']],
                ],
                'packages-dev' => [],
            ];
            $urls = VersionHelper::parseComposerLockUrls($lockData);
            expect($urls['a/b'])->toBe('https://github.com/a/b');
        });

        it('skips entries without name', function () {
            $lockData = [
                'packages' => [
                    ['source' => ['url' => 'https://github.com/a/b']],
                ],
                'packages-dev' => [],
            ];
            $urls = VersionHelper::parseComposerLockUrls($lockData);
            expect($urls)->toBe([]);
        });

        it('skips entries without source or homepage', function () {
            $lockData = [
                'packages' => [
                    ['name' => 'a/b', 'version' => '1.0.0'],
                ],
                'packages-dev' => [],
            ];
            $urls = VersionHelper::parseComposerLockUrls($lockData);
            expect($urls)->toBe([]);
        });

        it('prefers source url over homepage', function () {
            $lockData = [
                'packages' => [
                    ['name' => 'a/b', 'source' => ['url' => 'https://github.com/a/b.git'], 'homepage' => 'https://example.com'],
                ],
                'packages-dev' => [],
            ];
            $urls = VersionHelper::parseComposerLockUrls($lockData);
            expect($urls['a/b'])->toBe('https://github.com/a/b');
        });

        it('includes packages-dev URLs', function () {
            $lockData = [
                'packages' => [],
                'packages-dev' => [
                    ['name' => 'dev/pkg', 'source' => ['url' => 'https://github.com/dev/pkg.git']],
                ],
            ];
            $urls = VersionHelper::parseComposerLockUrls($lockData);
            expect($urls['dev/pkg'])->toBe('https://github.com/dev/pkg');
        });

        it('returns empty for empty lock data', function () {
            expect(VersionHelper::parseComposerLockUrls([]))->toBe([]);
        });
    });

    describe('extractComposerPhpVersion', function () {
        it('returns platform PHP version', function () {
            expect(VersionHelper::extractComposerPhpVersion([
                'platform' => ['php' => '8.4.2'],
            ]))->toBe('8.4.2');
        });

        it('prefers platform-overrides over platform', function () {
            expect(VersionHelper::extractComposerPhpVersion([
                'platform' => ['php' => '8.3.0'],
                'platform-overrides' => ['php' => '8.4.0'],
            ]))->toBe('8.4.0');
        });

        it('returns null when no platform keys exist', function () {
            expect(VersionHelper::extractComposerPhpVersion([]))->toBeNull();
        });

        it('returns null when platform exists but has no php key', function () {
            expect(VersionHelper::extractComposerPhpVersion([
                'platform' => ['ext-json' => '*'],
            ]))->toBeNull();
        });

        it('returns platform-overrides PHP even without platform', function () {
            expect(VersionHelper::extractComposerPhpVersion([
                'platform-overrides' => ['php' => '8.2.0'],
            ]))->toBe('8.2.0');
        });

        it('handles non-array platform value', function () {
            expect(VersionHelper::extractComposerPhpVersion([
                'platform' => 'invalid',
            ]))->toBeNull();
        });
    });
});
