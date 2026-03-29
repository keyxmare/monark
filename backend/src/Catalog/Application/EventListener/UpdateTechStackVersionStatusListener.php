<?php

declare(strict_types=1);

namespace App\Catalog\Application\EventListener;

use App\Catalog\Domain\Repository\TechStackRepositoryInterface;
use App\Shared\Domain\Event\ProductVersionsSyncedEvent;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class UpdateTechStackVersionStatusListener
{
    private const array LANGUAGE_REVERSE_MAP = [
        'php' => 'PHP',
        'python' => 'Python',
        'nodejs' => ['JavaScript', 'TypeScript', 'Node.js'],
        'go' => 'Go',
        'rust' => 'Rust',
        'ruby' => 'Ruby',
    ];

    private const array FRAMEWORK_REVERSE_MAP = [
        'symfony' => 'Symfony',
        'laravel' => 'Laravel',
        'vue' => 'Vue',
        'nuxt' => 'Nuxt',
        'angular' => 'Angular',
        'react' => 'React',
        'next.js' => 'Next.js',
        'django' => 'Django',
        'rails' => 'Rails',
    ];

    public function __construct(
        private TechStackRepositoryInterface $techStackRepository,
    ) {
    }

    public function __invoke(ProductVersionsSyncedEvent $event): void
    {
        if ($event->packageManager !== null) {
            return;
        }

        $frameworkName = self::FRAMEWORK_REVERSE_MAP[$event->productName] ?? null;
        $languageNames = self::LANGUAGE_REVERSE_MAP[$event->productName] ?? null;

        $techStacks = [];

        if ($frameworkName !== null) {
            $techStacks = $this->techStackRepository->findByFramework($frameworkName);
        } elseif ($languageNames !== null) {
            $names = \is_array($languageNames) ? $languageNames : [$languageNames];
            foreach ($names as $name) {
                \array_push($techStacks, ...$this->techStackRepository->findByLanguage($name));
            }
        }

        foreach ($techStacks as $ts) {
            $currentVersion = $frameworkName !== null ? $ts->getFrameworkVersion() : $ts->getVersion();
            $status = $this->computeStatus($currentVersion, $event);

            $eolDate = null;
            if ($status['eolDate'] !== null) {
                try {
                    $eolDate = new DateTimeImmutable($status['eolDate']);
                } catch (\Throwable) {
                }
            }

            $ts->updateVersionStatus(
                latestLts: $event->ltsVersion ?? $event->latestVersion,
                ltsGap: $status['gap'],
                maintenanceStatus: $status['status'],
                eolDate: $eolDate,
            );
            $this->techStackRepository->save($ts);
        }
    }

    /** @return array{status: string, gap: ?string, eolDate: ?string} */
    private function computeStatus(string $currentVersion, ProductVersionsSyncedEvent $event): array
    {
        if ($currentVersion === '' || $event->latestVersion === null) {
            return ['status' => 'active', 'gap' => null, 'eolDate' => null];
        }

        $eolDate = null;
        $currentMajorMinor = \implode('.', \array_slice(\explode('.', $currentVersion), 0, 2));
        $currentMajor = \explode('.', $currentVersion)[0];

        foreach ($event->eolCycles as $cycle) {
            $cycleMajorMinor = \implode('.', \array_slice(\explode('.', $cycle['version']), 0, 2));
            $cycleMajor = \explode('.', $cycle['version'])[0];
            if ($cycleMajorMinor === $currentMajorMinor || $cycleMajor === $currentMajor) {
                $eolDate = $cycle['eolDate'];
                break;
            }
        }

        $status = 'active';
        if ($eolDate !== null && $eolDate !== 'true') {
            try {
                $eol = new DateTimeImmutable($eolDate);
                if ($eol < new DateTimeImmutable()) {
                    $status = 'eol';
                }
            } catch (\Throwable) {
            }
        } elseif ($eolDate === 'true') {
            $status = 'eol';
        }

        $latestRef = $event->ltsVersion ?? $event->latestVersion;
        $gap = null;
        if (!\version_compare($currentVersion, $latestRef, '>=')) {
            $gap = $this->computeGap($currentVersion, $latestRef);
        }

        return ['status' => $status, 'gap' => $gap, 'eolDate' => $eolDate !== 'true' ? $eolDate : null];
    }

    private function computeGap(string $current, string $latest): string
    {
        $cParts = \explode('.', $current);
        $lParts = \explode('.', $latest);

        if ($cParts[0] === $lParts[0] && ($cParts[1] ?? '0') === ($lParts[1] ?? '0')) {
            $patchDiff = ((int) ($lParts[2] ?? 0)) - ((int) ($cParts[2] ?? 0));
            return \sprintf('%d patch(es)', $patchDiff);
        }

        return \sprintf('%s → %s', $current, $latest);
    }
}
