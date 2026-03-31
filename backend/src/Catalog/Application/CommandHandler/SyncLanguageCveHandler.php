<?php

declare(strict_types=1);

namespace App\Catalog\Application\CommandHandler;

use App\Catalog\Application\Command\SyncLanguageCveCommand;
use App\Catalog\Domain\Event\LanguageCveSyncedEvent;
use App\Catalog\Domain\Model\LanguageVulnerability;
use App\Catalog\Domain\Repository\LanguageRepositoryInterface;
use App\Catalog\Domain\Repository\LanguageVulnerabilityRepositoryInterface;
use App\Shared\Domain\DTO\OsvQuery;
use App\Shared\Domain\Port\OsvClientInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class SyncLanguageCveHandler
{
    private const array LANGUAGE_ECOSYSTEM_MAP = [
        'PHP' => 'Packagist',
        'JavaScript' => 'npm',
        'TypeScript' => 'npm',
        'Python' => 'PyPI',
        'Ruby' => 'RubyGems',
        'Go' => 'Go',
        'Rust' => 'crates.io',
    ];

    public function __construct(
        private LanguageRepositoryInterface $languageRepository,
        private LanguageVulnerabilityRepositoryInterface $vulnerabilityRepository,
        private OsvClientInterface $osvClient,
        private MessageBusInterface $eventBus,
    ) {
    }

    public function __invoke(SyncLanguageCveCommand $command): void
    {
        $projectId = Uuid::fromString($command->projectId);
        $languages = $this->languageRepository->findByProjectId($projectId);

        if ($languages === []) {
            $this->eventBus->dispatch(new LanguageCveSyncedEvent($command->projectId, 0));
            return;
        }

        $queries = [];
        $languageIndex = [];
        foreach ($languages as $language) {
            $ecosystem = self::LANGUAGE_ECOSYSTEM_MAP[$language->getName()] ?? null;
            if ($ecosystem === null) {
                continue;
            }
            $queries[] = new OsvQuery($ecosystem, \strtolower($language->getName()), $language->getVersion());
            $languageIndex[] = $language;
        }

        if ($queries === []) {
            $this->eventBus->dispatch(new LanguageCveSyncedEvent($command->projectId, 0));
            return;
        }

        $results = $this->osvClient->queryBatch($queries);

        $totalFound = 0;
        foreach ($results as $index => $vulns) {
            if (!isset($languageIndex[$index])) {
                continue;
            }
            $language = $languageIndex[$index];

            foreach ($vulns as $osvVuln) {
                $existing = $this->vulnerabilityRepository->findByOsvIdAndLanguageId(
                    $osvVuln->id,
                    $language->getId(),
                );

                if ($existing !== null) {
                    continue;
                }

                $vuln = LanguageVulnerability::create(
                    language: $language,
                    cveId: $osvVuln->cveId,
                    osvId: $osvVuln->id,
                    summary: $osvVuln->summary,
                    severity: $osvVuln->severity,
                    cvssScore: $osvVuln->cvssScore,
                    patchedVersion: $osvVuln->patchedVersion,
                );

                $this->vulnerabilityRepository->save($vuln);
                ++$totalFound;
            }
        }

        $this->eventBus->dispatch(new LanguageCveSyncedEvent($command->projectId, $totalFound));
    }
}
