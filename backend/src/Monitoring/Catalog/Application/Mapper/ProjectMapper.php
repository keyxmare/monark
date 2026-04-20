<?php

declare(strict_types=1);

namespace App\Monitoring\Catalog\Application\Mapper;

use App\Hub\Shared\Application\DTO\ProjectActivitySummary;
use App\Hub\Shared\Domain\ValueObject\VersionLag;
use App\Monitoring\Catalog\Application\DTO\FrameworkLagSummary;
use App\Monitoring\Catalog\Application\DTO\ProjectOutput;
use App\Monitoring\Catalog\Application\DTO\RuntimeSummary;
use App\Monitoring\Catalog\Application\DTO\TechStackSummary;
use App\Monitoring\Catalog\Domain\Model\Framework;
use App\Monitoring\Catalog\Domain\Model\Project;
use App\Monitoring\VersionRegistry\Domain\Model\Product;
use DateTimeInterface;

final class ProjectMapper
{
    public const int ACTIVITY_WINDOW_DAYS = 30;

    private const array LANGUAGE_TO_PRODUCT = [
        'PHP' => 'php',
        'Python' => 'python',
        'JavaScript' => 'nodejs',
        'TypeScript' => 'nodejs',
        'Node.js' => 'nodejs',
        'Go' => 'go',
        'Rust' => 'rust',
        'Ruby' => 'ruby',
    ];

    private const array LANGUAGES_WHERE_VERSION_IS_RUNTIME = [
        'PHP',
        'JavaScript',
        'Node.js',
        'Python',
        'Go',
        'Rust',
        'Ruby',
    ];

    private const array PRODUCT_DISPLAY_NAMES = [
        'php' => 'PHP',
        'python' => 'Python',
        'nodejs' => 'Node.js',
        'go' => 'Go',
        'rust' => 'Rust',
        'ruby' => 'Ruby',
    ];

    /**
     * @param list<Framework>                                             $frameworks
     * @param array<string, ?string>                                      $latestVersionByProduct Product name lowercase → latest stable version (null when unknown)
     * @param array<string, Product>                                      $languageProducts        Product name lowercase → Product entity (type=Language only)
     * @param array{critical: int, high: int, medium: int, low: int}|null $vulnerabilitiesBySeverity
     * @param list<array{name: string, percent: float}>                   $coverageJobs
     */
    public static function toOutput(
        Project $project,
        array $frameworks = [],
        ?float $coveragePercent = null,
        int $dependenciesCount = 0,
        int $outdatedDependenciesCount = 0,
        int $vulnerabilitiesCount = 0,
        ?ProjectActivitySummary $activity = null,
        array $latestVersionByProduct = [],
        array $languageProducts = [],
        ?array $vulnerabilitiesBySeverity = null,
        array $coverageJobs = [],
    ): ProjectOutput {
        $activity ??= ProjectActivitySummary::empty(self::ACTIVITY_WINDOW_DAYS);
        $techStacks = self::buildTechStacks($frameworks, $project->getLanguagesPalette());
        $runtimes = self::buildRuntimes($frameworks, $languageProducts);
        $lag = self::summarizeLag($frameworks, $latestVersionByProduct);

        return new ProjectOutput(
            id: $project->getId()->toRfc4122(),
            name: $project->getName(),
            slug: $project->getSlug(),
            description: $project->getDescription(),
            repositoryUrl: $project->getRepositoryUrl(),
            defaultBranch: $project->getDefaultBranch(),
            visibility: $project->getVisibility()->value,
            ownerId: $project->getOwnerId()->toRfc4122(),
            providerId: $project->getProvider()?->getId()->toRfc4122(),
            externalId: $project->getExternalId(),
            createdAt: $project->getCreatedAt()->format(DateTimeInterface::ATOM),
            updatedAt: $project->getUpdatedAt()->format(DateTimeInterface::ATOM),
            techStacks: $techStacks,
            techStacksCount: \count($techStacks),
            runtimes: $runtimes,
            frameworkLag: $lag,
            coveragePercent: $coveragePercent,
            coverageJobs: $coverageJobs,
            dependenciesCount: $dependenciesCount,
            outdatedDependenciesCount: $outdatedDependenciesCount,
            vulnerabilitiesCount: $vulnerabilitiesCount,
            vulnerabilitiesBySeverity: $vulnerabilitiesBySeverity ?? ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0],
            lastActivityAt: $activity->lastActivityAt?->format(DateTimeInterface::ATOM),
            lastCommitSha: $activity->lastCommitSha,
            commitsLast30d: $activity->commitsCount,
            commitsDailySeries: $activity->dailyCommitCounts,
        );
    }

    /**
     * @param list<Framework>        $frameworks
     * @param array<string, Product> $languageProducts Keyed by lowercase product name
     *
     * @return list<RuntimeSummary>
     */
    private static function buildRuntimes(array $frameworks, array $languageProducts): array
    {
        $minByProduct = [];
        foreach ($frameworks as $framework) {
            $language = $framework->getLanguageName();
            $productKey = self::LANGUAGE_TO_PRODUCT[$language] ?? null;
            if ($productKey === null) {
                continue;
            }
            $minByProduct[$productKey] ??= null;
            if (!\in_array($language, self::LANGUAGES_WHERE_VERSION_IS_RUNTIME, true)) {
                continue;
            }
            $raw = $framework->getLanguageVersion();
            if ($raw === '') {
                continue;
            }
            $minByProduct[$productKey] ??= $raw;
        }

        $runtimes = [];
        foreach ($minByProduct as $productKey => $minVersion) {
            $product = $languageProducts[$productKey] ?? null;
            $runtimes[] = new RuntimeSummary(
                name: self::PRODUCT_DISPLAY_NAMES[$productKey],
                productKey: $productKey,
                minVersion: $minVersion,
                latestVersion: $product?->getLatestVersion(),
                ltsVersion: $product?->getLtsVersion(),
            );
        }

        return $runtimes;
    }

    /**
     * @param list<Framework>         $frameworks
     * @param array<string, ?string>  $latestVersionByProduct
     */
    private static function summarizeLag(array $frameworks, array $latestVersionByProduct): FrameworkLagSummary
    {
        if ($frameworks === []) {
            return FrameworkLagSummary::empty();
        }

        $major = 0;
        $minor = 0;
        $patch = 0;
        $upToDate = 0;
        $unknown = 0;
        foreach ($frameworks as $framework) {
            $reference = $latestVersionByProduct[\strtolower($framework->getName())]
                ?? $framework->getLatestLts();
            $lag = VersionLag::classify($framework->getVersion(), $reference);
            match ($lag) {
                VersionLag::Major => ++$major,
                VersionLag::Minor => ++$minor,
                VersionLag::Patch => ++$patch,
                VersionLag::UpToDate => ++$upToDate,
                VersionLag::Unknown => ++$unknown,
            };
        }

        return new FrameworkLagSummary($major, $minor, $patch, $upToDate, $unknown);
    }

    /**
     * Merge framework-level detection (language + framework + version) with the provider's raw language palette.
     * Frameworks take precedence for their language; remaining palette languages are appended without framework.
     *
     * @param list<Framework>     $frameworks
     * @param array<string, int>  $palette Byte-count per language, descending
     *
     * @return list<TechStackSummary>
     */
    private static function buildTechStacks(array $frameworks, array $palette): array
    {
        $stacks = [];
        $covered = [];
        foreach ($frameworks as $framework) {
            $lang = $framework->getLanguageName();
            $stacks[] = new TechStackSummary(
                language: $lang,
                framework: $framework->getName() !== '' ? $framework->getName() : null,
                version: $framework->getVersion() !== '' ? $framework->getVersion() : null,
            );
            $covered[\strtolower($lang)] = true;
        }

        foreach ($palette as $language => $_bytes) {
            if (isset($covered[\strtolower($language)])) {
                continue;
            }
            $stacks[] = new TechStackSummary(
                language: $language,
                framework: null,
                version: null,
            );
        }

        return $stacks;
    }
}
