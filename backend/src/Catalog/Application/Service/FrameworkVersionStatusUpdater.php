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
use Throwable;

final readonly class FrameworkVersionStatusUpdater
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
        private FrameworkRepositoryInterface $frameworkRepository,
        private ProductVersionRepositoryInterface $productVersionRepository,
        private ProductRepositoryInterface $productRepository,
        private MessageBusInterface $eventBus,
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
            try {
                $current = SemanticVersion::parse($currentVersion);
                $latest = SemanticVersion::parse($latestLts);
                if ($latest->isNewerThan($current)) {
                    $gap = $this->computeGap($current, $latest);
                }
            } catch (InvalidArgumentException) {
            }
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

    private function computeGap(SemanticVersion $current, SemanticVersion $latest): string
    {
        if ($current->major === $latest->major && $current->minor === $latest->minor) {
            return \sprintf('%d patch(es)', $current->getPatchGap($latest));
        }

        return \sprintf('%s → %s', $current, $latest);
    }
}
