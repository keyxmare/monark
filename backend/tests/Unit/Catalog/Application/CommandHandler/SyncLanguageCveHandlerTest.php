<?php

declare(strict_types=1);

use App\Catalog\Application\Command\SyncLanguageCveCommand;
use App\Catalog\Application\CommandHandler\SyncLanguageCveHandler;
use App\Catalog\Domain\Event\LanguageCveSyncedEvent;
use App\Catalog\Domain\Model\Language;
use App\Catalog\Domain\Model\LanguageVulnerability;
use App\Catalog\Domain\Repository\LanguageRepositoryInterface;
use App\Catalog\Domain\Repository\LanguageVulnerabilityRepositoryInterface;
use App\Shared\Domain\DTO\OsvVulnerability;
use App\Shared\Domain\Port\OsvClientInterface;
use App\Shared\Domain\ValueObject\Severity;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Tests\Factory\Catalog\LanguageFactory;
use Tests\Factory\Catalog\ProjectFactory;

function stubLangCveLanguageRepo(array $languages = []): LanguageRepositoryInterface
{
    return new class ($languages) implements LanguageRepositoryInterface {
        public function __construct(private readonly array $langs)
        {
        }
        public function findById(Uuid $id): ?Language
        {
            return null;
        }
        public function findAll(): array
        {
            return [];
        }
        public function findByProjectId(Uuid $projectId): array
        {
            return $this->langs;
        }
        public function findByNameAndProjectId(string $name, Uuid $projectId): ?Language
        {
            return null;
        }
        public function findByName(string $name): array
        {
            return [];
        }
        public function save(Language $language): void
        {
        }
        public function delete(Language $language): void
        {
        }
        public function deleteByProjectId(Uuid $projectId): void
        {
        }
    };
}

function stubLangCveVulnRepo(array $existingByOsvId = []): LanguageVulnerabilityRepositoryInterface
{
    return new class ($existingByOsvId) implements LanguageVulnerabilityRepositoryInterface {
        /** @var list<LanguageVulnerability> */
        public array $saved = [];
        public function __construct(private readonly array $existingByOsvId)
        {
        }
        public function save(LanguageVulnerability $vulnerability): void
        {
            $this->saved[] = $vulnerability;
        }
        public function findByLanguageId(Uuid $languageId): array
        {
            return [];
        }
        public function findByOsvIdAndLanguageId(string $osvId, Uuid $languageId): ?LanguageVulnerability
        {
            return $this->existingByOsvId[$osvId] ?? null;
        }
        public function findById(Uuid $id): ?LanguageVulnerability
        {
            return null;
        }
        public function findAll(int $page = 1, int $perPage = 20): array
        {
            return [];
        }
        public function count(): int
        {
            return 0;
        }
        public function deleteByLanguageId(Uuid $languageId): void
        {
        }
    };
}

function stubLangCveOsvClient(array $batchResults = []): OsvClientInterface
{
    return new class ($batchResults) implements OsvClientInterface {
        public array $queriedBatches = [];
        public function __construct(private readonly array $batchResults)
        {
        }
        public function queryPackage(string $ecosystem, string $name, string $version): array
        {
            return [];
        }
        public function queryBatch(array $queries): array
        {
            $this->queriedBatches[] = $queries;
            return $this->batchResults;
        }
    };
}

function spyLangCveEventBus(): object
{
    return new class () implements MessageBusInterface {
        /** @var list<object> */
        public array $dispatched = [];
        public function dispatch(object $message, array $stamps = []): Envelope
        {
            $msg = $message instanceof Envelope ? $message->getMessage() : $message;
            $this->dispatched[] = $msg;
            return Envelope::wrap($message, $stamps);
        }
    };
}

describe('SyncLanguageCveHandler', function () {
    it('creates language vulnerabilities from OSV results', function () {
        $project = ProjectFactory::create();
        $language = LanguageFactory::create(name: 'PHP', version: '8.4', project: $project);

        $langRepo = stubLangCveLanguageRepo([$language]);
        $vulnRepo = stubLangCveVulnRepo();
        $osvClient = stubLangCveOsvClient([
            [
                new OsvVulnerability(
                    id: 'GHSA-xxxx',
                    cveId: 'CVE-2024-0001',
                    summary: 'Remote code execution in PHP',
                    severity: Severity::Critical,
                    cvssScore: 9.8,
                    patchedVersion: '8.4.1',
                    references: [],
                    publishedAt: new DateTimeImmutable(),
                ),
            ],
        ]);
        $eventBus = spyLangCveEventBus();

        $handler = new SyncLanguageCveHandler($langRepo, $vulnRepo, $osvClient, $eventBus);
        $handler(new SyncLanguageCveCommand($project->getId()->toRfc4122()));

        expect($vulnRepo->saved)->toHaveCount(1)
            ->and($vulnRepo->saved[0])->toBeInstanceOf(LanguageVulnerability::class)
            ->and($vulnRepo->saved[0]->getOsvId())->toBe('GHSA-xxxx')
            ->and($vulnRepo->saved[0]->getCveId())->toBe('CVE-2024-0001')
            ->and($vulnRepo->saved[0]->getSeverity())->toBe(Severity::Critical)
            ->and($eventBus->dispatched)->toHaveCount(1)
            ->and($eventBus->dispatched[0])->toBeInstanceOf(LanguageCveSyncedEvent::class)
            ->and($eventBus->dispatched[0]->vulnerabilitiesFound)->toBe(1);
    });

    it('skips already tracked vulnerabilities', function () {
        $project = ProjectFactory::create();
        $language = LanguageFactory::create(name: 'PHP', version: '8.4', project: $project);

        $existingVuln = LanguageVulnerability::create(
            language: $language,
            cveId: 'CVE-2024-0001',
            osvId: 'GHSA-xxxx',
            summary: 'Already known',
            severity: Severity::Critical,
            cvssScore: 9.8,
            patchedVersion: '8.4.1',
        );

        $langRepo = stubLangCveLanguageRepo([$language]);
        $vulnRepo = stubLangCveVulnRepo(['GHSA-xxxx' => $existingVuln]);
        $osvClient = stubLangCveOsvClient([
            [
                new OsvVulnerability(
                    id: 'GHSA-xxxx',
                    cveId: 'CVE-2024-0001',
                    summary: 'Remote code execution in PHP',
                    severity: Severity::Critical,
                    cvssScore: 9.8,
                    patchedVersion: '8.4.1',
                    references: [],
                    publishedAt: new DateTimeImmutable(),
                ),
            ],
        ]);
        $eventBus = spyLangCveEventBus();

        $handler = new SyncLanguageCveHandler($langRepo, $vulnRepo, $osvClient, $eventBus);
        $handler(new SyncLanguageCveCommand($project->getId()->toRfc4122()));

        expect($vulnRepo->saved)->toHaveCount(0)
            ->and($eventBus->dispatched)->toHaveCount(1)
            ->and($eventBus->dispatched[0]->vulnerabilitiesFound)->toBe(0);
    });

    it('dispatches event with zero when no languages found', function () {
        $projectId = Uuid::v7();
        $langRepo = stubLangCveLanguageRepo([]);
        $vulnRepo = stubLangCveVulnRepo();
        $osvClient = stubLangCveOsvClient([]);
        $eventBus = spyLangCveEventBus();

        $handler = new SyncLanguageCveHandler($langRepo, $vulnRepo, $osvClient, $eventBus);
        $handler(new SyncLanguageCveCommand($projectId->toRfc4122()));

        expect($eventBus->dispatched)->toHaveCount(1)
            ->and($eventBus->dispatched[0])->toBeInstanceOf(LanguageCveSyncedEvent::class)
            ->and($eventBus->dispatched[0]->vulnerabilitiesFound)->toBe(0);
    });

    it('skips languages with unknown ecosystem', function () {
        $project = ProjectFactory::create();
        $language = LanguageFactory::create(name: 'Haskell', version: '9.8', project: $project);

        $langRepo = stubLangCveLanguageRepo([$language]);
        $vulnRepo = stubLangCveVulnRepo();
        $osvClient = stubLangCveOsvClient([]);
        $eventBus = spyLangCveEventBus();

        $handler = new SyncLanguageCveHandler($langRepo, $vulnRepo, $osvClient, $eventBus);
        $handler(new SyncLanguageCveCommand($project->getId()->toRfc4122()));

        expect($osvClient->queriedBatches)->toHaveCount(0)
            ->and($eventBus->dispatched)->toHaveCount(1)
            ->and($eventBus->dispatched[0]->vulnerabilitiesFound)->toBe(0);
    });
});
