<?php

declare(strict_types=1);

namespace App\Catalog\Application\Service;

use App\Catalog\Domain\Model\Framework;
use App\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use App\Dependency\Domain\ValueObject\SemanticVersion;
use App\VersionRegistry\Domain\Model\ProductVersion;
use App\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use App\VersionRegistry\Domain\Repository\ProductVersionRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

final class FrameworkVersionStatusUpdater
{
    private const array FRAMEWORK_MAP = [
        'Symfony' => 'symfony',
        'Laravel' => 'laravel',
        'Vue' => 'vue',
        'Nuxt' => 'nuxt',
        'Angular' => 'angular',
        'React' => 'react',
        'Next.js' => 'next.js',
        'Django' => 'django',
        'Rails' => 'rails',
    ];

    private const array NPM_PACKAGE_MAP = [
        'vue' => 'vue',
        'nuxt' => 'nuxt',
        'angular' => '@angular/core',
        'react' => 'react',
        'next.js' => 'next',
    ];

    private const array PACKAGIST_PACKAGE_MAP = [
        'symfony' => 'symfony/symfony',
        'laravel' => 'laravel/framework',
    ];

    /** @var array<string, array<string, string>> */
    private array $registryTimeCache = [];

    public function __construct(
        private readonly FrameworkRepositoryInterface $frameworkRepository,
        private readonly ProductVersionRepositoryInterface $productVersionRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly MessageBusInterface $eventBus,
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /** @param list<Framework> $frameworks */
    public function refreshAll(array $frameworks): int
    {
        $cache = [];
        $updated = 0;

        foreach ($frameworks as $fw) {
            if ($this->refreshOne($fw, $cache)) {
                $updated++;
            }
        }

        return $updated;
    }

    /** @param array<string, array{latestLts: ?string, versions: list<ProductVersion>}> $cache */
    private function refreshOne(Framework $fw, array &$cache): bool
    {
        $productName = self::FRAMEWORK_MAP[$fw->getName()] ?? null;
        if ($productName === null) {
            return false;
        }

        if (!isset($cache[$productName])) {
            $product = $this->productRepository->findByNameAndManager($productName, null);
            $cache[$productName] = [
                'latestLts' => $product?->getLtsVersion() ?? $product?->getLatestVersion(),
                'versions' => $this->productVersionRepository->findByNameAndManager($productName, null),
            ];
        }

        $latestLts = $cache[$productName]['latestLts'];
        $allVersions = $cache[$productName]['versions'];

        if ($allVersions === []) {
            return false;
        }

        $currentVersion = $fw->getVersion();
        $eolDateStr = $this->findEolDate($currentVersion, $allVersions);

        $status = 'active';
        $eolDate = null;

        if ($eolDateStr !== null && $eolDateStr !== 'true') {
            try {
                $eolDate = new DateTimeImmutable($eolDateStr);
                if ($eolDate < new DateTimeImmutable()) {
                    $status = 'eol';
                }
            } catch (Throwable) {
            }
        } elseif ($eolDateStr === 'true') {
            $status = 'eol';
        }

        $gap = null;
        if ($latestLts !== null) {
            $gap = $this->computeDateGap($currentVersion, $latestLts, $productName, $allVersions);
        }

        $fw->updateVersionStatus(
            latestLts: $latestLts,
            ltsGap: $gap,
            maintenanceStatus: $status,
            eolDate: $eolDate,
        );
        $this->frameworkRepository->save($fw);

        foreach ($fw->pullDomainEvents() as $event) {
            $this->eventBus->dispatch($event);
        }

        return true;
    }

    /** @param list<ProductVersion> $allVersions */
    private function findEolDate(string $currentVersion, array $allVersions): ?string
    {
        try {
            $current = SemanticVersion::parse($currentVersion);
        } catch (InvalidArgumentException) {
            return null;
        }

        foreach ($allVersions as $pv) {
            try {
                $pvParsed = SemanticVersion::parse($pv->getVersion());
            } catch (InvalidArgumentException) {
                continue;
            }

            if ($pvParsed->major === $current->major && $pvParsed->minor === $current->minor && $pv->getEolDate() !== null) {
                return $pv->getEolDate();
            }
        }

        return null;
    }

    /** @param list<ProductVersion> $allVersions */
    private function computeDateGap(string $currentVersion, string $latestLtsVersion, string $productName, array $allVersions): ?string
    {
        $currentDate = $this->findReleaseDate($currentVersion, $productName, $allVersions);
        $ltsDate = $this->findReleaseDate($latestLtsVersion, $productName, $allVersions);

        if ($currentDate === null || $ltsDate === null) {
            return null;
        }

        $diff = $currentDate->diff($ltsDate);
        $days = (int) $diff->format('%r%a');

        if ($days <= 0) {
            return null;
        }

        if ($days >= 365) {
            $years = \intdiv($days, 365);
            $remainingMonths = \intdiv($days % 365, 30);
            return $remainingMonths > 0
                ? \sprintf('%da %dm', $years, $remainingMonths)
                : \sprintf('%da', $years);
        }

        if ($days >= 30) {
            return \sprintf('%dm', \intdiv($days, 30));
        }

        return \sprintf('%dj', $days);
    }

    /** @param list<ProductVersion> $allVersions */
    private function findReleaseDate(string $version, string $productName, array $allVersions): ?DateTimeImmutable
    {
        try {
            $target = SemanticVersion::parse($version);
        } catch (InvalidArgumentException) {
            return null;
        }

        $minorFallback = null;

        foreach ($allVersions as $pv) {
            try {
                $pvParsed = SemanticVersion::parse($pv->getVersion());
            } catch (InvalidArgumentException) {
                continue;
            }

            if ($pvParsed->major !== $target->major || $pvParsed->minor !== $target->minor || $pv->getReleaseDate() === null) {
                continue;
            }

            if ($pvParsed->patch === $target->patch) {
                return $pv->getReleaseDate();
            }

            if ($minorFallback === null || $pv->getReleaseDate() > $minorFallback) {
                $minorFallback = $pv->getReleaseDate();
            }
        }

        if ($minorFallback !== null) {
            return $minorFallback;
        }

        return $this->fetchReleaseDateFromRegistry($productName, $version);
    }

    private function fetchReleaseDateFromRegistry(string $productName, string $version): ?DateTimeImmutable
    {
        $npmPackage = self::NPM_PACKAGE_MAP[$productName] ?? null;
        if ($npmPackage !== null) {
            return $this->fetchNpmReleaseDate($npmPackage, $version);
        }

        $packagistPackage = self::PACKAGIST_PACKAGE_MAP[$productName] ?? null;
        if ($packagistPackage !== null) {
            return $this->fetchPackagistReleaseDate($packagistPackage, $version);
        }

        return null;
    }

    private function fetchNpmReleaseDate(string $packageName, string $version): ?DateTimeImmutable
    {
        $times = $this->getNpmTimes($packageName);

        $dateStr = $times[$version] ?? null;
        if ($dateStr === null) {
            return null;
        }

        try {
            return new DateTimeImmutable($dateStr);
        } catch (Throwable) {
            return null;
        }
    }

    /** @return array<string, string> */
    private function getNpmTimes(string $packageName): array
    {
        if (isset($this->registryTimeCache[$packageName])) {
            return $this->registryTimeCache[$packageName];
        }

        try {
            $encodedName = \str_contains($packageName, '/') ? \rawurlencode($packageName) : $packageName;
            $response = $this->httpClient->request('GET', \sprintf('https://registry.npmjs.org/%s', $encodedName), [
                'headers' => ['Accept' => 'application/json'],
                'timeout' => 10,
            ]);

            /** @var array{time?: array<string, string>} $data */
            $data = $response->toArray();

            $this->registryTimeCache[$packageName] = $data['time'] ?? [];
        } catch (Throwable) {
            $this->registryTimeCache[$packageName] = [];
        }

        return $this->registryTimeCache[$packageName];
    }

    private function fetchPackagistReleaseDate(string $packageName, string $version): ?DateTimeImmutable
    {
        try {
            $response = $this->httpClient->request('GET', \sprintf('https://repo.packagist.org/p2/%s.json', $packageName), [
                'headers' => ['Accept' => 'application/json'],
                'timeout' => 10,
            ]);

            /** @var array{packages?: array<string, list<array{version: string, time?: string}>>} $data */
            $data = $response->toArray();

            foreach ($data['packages'][$packageName] ?? [] as $release) {
                $releaseVersion = \ltrim($release['version'], 'v');
                if ($releaseVersion === $version && isset($release['time'])) {
                    return new DateTimeImmutable($release['time']);
                }
            }
        } catch (Throwable) {
        }

        return null;
    }
}
