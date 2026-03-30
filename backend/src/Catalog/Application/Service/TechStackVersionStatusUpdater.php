<?php

declare(strict_types=1);

namespace App\Catalog\Application\Service;

use App\Catalog\Domain\Model\TechStack;
use App\Catalog\Domain\Repository\TechStackRepositoryInterface;
use App\Dependency\Domain\ValueObject\SemanticVersion;
use App\VersionRegistry\Domain\Model\ProductVersion;
use App\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use App\VersionRegistry\Domain\Repository\ProductVersionRepositoryInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

final readonly class TechStackVersionStatusUpdater
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

    public function __construct(
        private TechStackRepositoryInterface $techStackRepository,
        private ProductVersionRepositoryInterface $productVersionRepository,
        private ProductRepositoryInterface $productRepository,
        private MessageBusInterface $eventBus,
    ) {
    }

    /** @param list<TechStack> $stacks */
    public function refreshAll(array $stacks): int
    {
        $cache = [];
        $updated = 0;

        foreach ($stacks as $ts) {
            if ($this->refreshOne($ts, $cache)) {
                $updated++;
            }
        }

        return $updated;
    }

    /** @param array<string, array{latestLts: ?string, versions: list<ProductVersion>}> $cache */
    private function refreshOne(TechStack $ts, array &$cache): bool
    {
        $productName = self::FRAMEWORK_MAP[$ts->getFramework()] ?? null;
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

        $currentVersion = $ts->getFrameworkVersion();
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
            try {
                $current = SemanticVersion::parse($currentVersion);
                $latest = SemanticVersion::parse($latestLts);
                if ($latest->isNewerThan($current)) {
                    $gap = $this->computeGap($current, $latest);
                }
            } catch (InvalidArgumentException) {
            }
        }

        $ts->updateVersionStatus(
            latestLts: $latestLts,
            ltsGap: $gap,
            maintenanceStatus: $status,
            eolDate: $eolDate,
        );
        $this->techStackRepository->save($ts);

        foreach ($ts->pullDomainEvents() as $event) {
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
                $pv_parsed = SemanticVersion::parse($pv->getVersion());
            } catch (InvalidArgumentException) {
                continue;
            }

            if ($pv_parsed->major === $current->major && $pv_parsed->minor === $current->minor && $pv->getEolDate() !== null) {
                return $pv->getEolDate();
            }
        }

        foreach ($allVersions as $pv) {
            try {
                $pv_parsed = SemanticVersion::parse($pv->getVersion());
            } catch (InvalidArgumentException) {
                continue;
            }

            if ($pv_parsed->major === $current->major && $pv->getEolDate() !== null) {
                return $pv->getEolDate();
            }
        }

        return null;
    }

    private function computeGap(SemanticVersion $current, SemanticVersion $latest): string
    {
        if ($current->major === $latest->major && $current->minor === $latest->minor) {
            return \sprintf('%d patch(es)', $current->getPatchGap($latest));
        }

        return \sprintf('%s → %s', $current, $latest);
    }
}
