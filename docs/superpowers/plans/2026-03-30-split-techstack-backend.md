# Plan: Split TechStack → Language + Framework (Backend)

**Date**: 2026-03-30
**Objectif**: Remplacer l'entité `TechStack` par deux entités distinctes `Language` et `Framework` dans le bounded context Catalog.
**Architecture**: DDD/CQRS, Symfony 8, PHP 8.4, Doctrine ORM, Pest 4
**Spec**: `docs/superpowers/specs/2026-03-30-split-techstack-language-framework-design.md`

---

## Vue d'ensemble

```
TechStack (1 entité)
  language, framework, version, frameworkVersion
       ↓
Language (entité 1)          Framework (entité 2)
  name, version, eolDate       name, version, latestLts, ltsGap
  maintenanceStatus            maintenanceStatus, eolDate
  projectId                    versionSyncedAt, languageId, projectId
```

**Règle clé**: pas de migration de données — le prochain scan repopule les tables.

---

## Tâche 1 — Migration DB

**Fichiers créés**:
- `backend/migrations/Version20260330100000.php`

**Steps**:

- [ ] Créer la migration Doctrine

```php
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260330100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace catalog_tech_stacks with catalog_languages and catalog_frameworks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS catalog_tech_stacks');

        $this->addSql('CREATE TABLE catalog_languages (
            id UUID NOT NULL,
            name VARCHAR(100) NOT NULL,
            version VARCHAR(50) NOT NULL,
            detected_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            eol_date DATE DEFAULT NULL,
            maintenance_status VARCHAR(20) DEFAULT NULL,
            project_id UUID NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_languages_project ON catalog_languages (project_id)');
        $this->addSql('ALTER TABLE catalog_languages ADD CONSTRAINT fk_languages_project
            FOREIGN KEY (project_id) REFERENCES catalog_projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE catalog_frameworks (
            id UUID NOT NULL,
            name VARCHAR(100) NOT NULL,
            version VARCHAR(50) NOT NULL,
            detected_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            latest_lts VARCHAR(50) DEFAULT NULL,
            lts_gap VARCHAR(100) DEFAULT NULL,
            maintenance_status VARCHAR(20) DEFAULT NULL,
            eol_date DATE DEFAULT NULL,
            version_synced_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            language_id UUID NOT NULL,
            project_id UUID NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_frameworks_project ON catalog_frameworks (project_id)');
        $this->addSql('CREATE INDEX idx_frameworks_language ON catalog_frameworks (language_id)');
        $this->addSql('ALTER TABLE catalog_frameworks ADD CONSTRAINT fk_frameworks_language
            FOREIGN KEY (language_id) REFERENCES catalog_languages (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE catalog_frameworks ADD CONSTRAINT fk_frameworks_project
            FOREIGN KEY (project_id) REFERENCES catalog_projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE catalog_frameworks DROP CONSTRAINT fk_frameworks_language');
        $this->addSql('ALTER TABLE catalog_frameworks DROP CONSTRAINT fk_frameworks_project');
        $this->addSql('DROP TABLE catalog_frameworks');
        $this->addSql('ALTER TABLE catalog_languages DROP CONSTRAINT fk_languages_project');
        $this->addSql('DROP TABLE catalog_languages');

        $this->addSql('CREATE TABLE catalog_tech_stacks (
            id UUID NOT NULL,
            language VARCHAR(100) NOT NULL,
            framework VARCHAR(100) NOT NULL,
            version VARCHAR(50) NOT NULL,
            framework_version VARCHAR(50) NOT NULL,
            detected_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            project_id UUID NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            latest_lts VARCHAR(50) DEFAULT NULL,
            lts_gap VARCHAR(100) DEFAULT NULL,
            maintenance_status VARCHAR(20) DEFAULT NULL,
            eol_date DATE DEFAULT NULL,
            version_synced_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
    }
}
```

- [ ] Appliquer la migration

```bash
docker exec monark-backend-1 php bin/console doctrine:migrations:migrate --no-interaction
```

---

## Tâche 2 — Entité Language

**Fichiers créés**:
- `backend/src/Catalog/Domain/Model/Language.php`
- `backend/src/Catalog/Domain/Event/LanguageStatusUpdated.php`

**Steps**:

- [ ] Créer l'event domain

```php
<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Event;

final readonly class LanguageStatusUpdated
{
    public function __construct(
        public string $languageId,
        public string $projectId,
        public string $language,
        public ?string $maintenanceStatus,
    ) {
    }
}
```

- [ ] Créer l'entité Language

```php
<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Model;

use App\Shared\Domain\Model\RecordsDomainEvents;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'catalog_languages')]
#[ORM\HasLifecycleCallbacks]
final class Language
{
    use RecordsDomainEvents;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 50)]
    private string $version;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $detectedAt;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?DateTimeImmutable $eolDate = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $maintenanceStatus = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    private function __construct(
        Uuid $id,
        string $name,
        string $version,
        DateTimeImmutable $detectedAt,
        Project $project,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->version = $version;
        $this->detectedAt = $detectedAt;
        $this->project = $project;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public static function create(
        string $name,
        string $version,
        DateTimeImmutable $detectedAt,
        Project $project,
    ): self {
        return new self(
            id: Uuid::v7(),
            name: $name,
            version: $version,
            detectedAt: $detectedAt,
            project: $project,
        );
    }

    public function getId(): Uuid { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getVersion(): string { return $this->version; }
    public function getDetectedAt(): DateTimeImmutable { return $this->detectedAt; }
    public function getEolDate(): ?DateTimeImmutable { return $this->eolDate; }
    public function getMaintenanceStatus(): ?string { return $this->maintenanceStatus; }
    public function getProject(): Project { return $this->project; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    public function updateStatus(?string $maintenanceStatus, ?DateTimeImmutable $eolDate): void
    {
        $this->maintenanceStatus = $maintenanceStatus;
        $this->eolDate = $eolDate;
        $this->updatedAt = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
```

---

## Tâche 3 — Entité Framework

**Fichiers créés**:
- `backend/src/Catalog/Domain/Model/Framework.php`
- `backend/src/Catalog/Domain/Event/FrameworkVersionStatusUpdated.php`
- `backend/src/Catalog/Domain/ValueObject/FrameworkHealth.php`
- `backend/src/Catalog/Domain/Service/FrameworkHealthCalculator.php`

**Steps**:

- [ ] Créer l'event domain

```php
<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Event;

final readonly class FrameworkVersionStatusUpdated
{
    public function __construct(
        public string $frameworkId,
        public string $projectId,
        public string $framework,
        public ?string $latestLts,
        public ?string $maintenanceStatus,
    ) {
    }
}
```

- [ ] Créer FrameworkHealth (renommage de TechStackHealth)

```php
<?php

declare(strict_types=1);

namespace App\Catalog\Domain\ValueObject;

use App\Dependency\Domain\ValueObject\RiskLevel;
use InvalidArgumentException;

final readonly class FrameworkHealth
{
    public function __construct(
        private int $score,
        private RiskLevel $riskLevel,
    ) {
        if ($score < 0 || $score > 100) {
            throw new InvalidArgumentException(\sprintf('FrameworkHealth score must be between 0 and 100, got %d.', $score));
        }
    }

    public function getScore(): int { return $this->score; }
    public function getRiskLevel(): RiskLevel { return $this->riskLevel; }
    public function isHealthy(): bool { return $this->score >= 60; }
}
```

- [ ] Créer FrameworkHealthCalculator (renommage de TechStackHealthCalculator)

```php
<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Service;

use App\Catalog\Domain\ValueObject\FrameworkHealth;
use App\Catalog\Domain\ValueObject\MaintenanceStatus;
use App\Dependency\Domain\ValueObject\RiskLevel;
use App\Dependency\Domain\ValueObject\SemanticVersion;

final class FrameworkHealthCalculator
{
    private const int PENALTY_EOL = 50;
    private const int PENALTY_MAJOR_GAP = 30;
    private const int PENALTY_MINOR_GAP_THRESHOLD = 3;
    private const int PENALTY_MINOR_GAP = 15;
    private const int SCORE_UNKNOWN = 80;

    public function calculate(
        SemanticVersion $current,
        SemanticVersion $latest,
        MaintenanceStatus $status,
    ): FrameworkHealth {
        $score = 100;

        if ($status === MaintenanceStatus::Eol) {
            $score -= self::PENALTY_EOL;
        }

        $majorGap = $latest->getMajorGap($current);
        if ($majorGap > 0) {
            $score -= $majorGap * self::PENALTY_MAJOR_GAP;
        }

        if ($majorGap === 0) {
            $minorGap = $latest->getMinorGap($current);
            if ($minorGap >= self::PENALTY_MINOR_GAP_THRESHOLD) {
                $score -= self::PENALTY_MINOR_GAP;
            }
        }

        $score = \max(0, $score);

        return new FrameworkHealth(score: $score, riskLevel: $this->scoreToRisk($score));
    }

    public function calculateUnknown(): FrameworkHealth
    {
        return new FrameworkHealth(score: self::SCORE_UNKNOWN, riskLevel: RiskLevel::None);
    }

    private function scoreToRisk(int $score): RiskLevel
    {
        return match (true) {
            $score < 30 => RiskLevel::Critical,
            $score < 50 => RiskLevel::High,
            $score < 70 => RiskLevel::Medium,
            $score < 90 => RiskLevel::Low,
            default => RiskLevel::None,
        };
    }
}
```

- [ ] Créer l'entité Framework

```php
<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Model;

use App\Catalog\Domain\Event\FrameworkVersionStatusUpdated;
use App\Shared\Domain\Model\RecordsDomainEvents;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'catalog_frameworks')]
#[ORM\HasLifecycleCallbacks]
final class Framework
{
    use RecordsDomainEvents;

    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 50)]
    private string $version;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $detectedAt;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $latestLts = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ltsGap = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $maintenanceStatus = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?DateTimeImmutable $eolDate = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $versionSyncedAt = null;

    #[ORM\ManyToOne(targetEntity: Language::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Language $language;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    private function __construct(
        Uuid $id,
        string $name,
        string $version,
        DateTimeImmutable $detectedAt,
        Language $language,
        Project $project,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->version = $version;
        $this->detectedAt = $detectedAt;
        $this->language = $language;
        $this->project = $project;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public static function create(
        string $name,
        string $version,
        DateTimeImmutable $detectedAt,
        Language $language,
        Project $project,
    ): self {
        return new self(
            id: Uuid::v7(),
            name: $name,
            version: $version,
            detectedAt: $detectedAt,
            language: $language,
            project: $project,
        );
    }

    public function getId(): Uuid { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getVersion(): string { return $this->version; }
    public function getDetectedAt(): DateTimeImmutable { return $this->detectedAt; }
    public function getLatestLts(): ?string { return $this->latestLts; }
    public function getLtsGap(): ?string { return $this->ltsGap; }
    public function getMaintenanceStatus(): ?string { return $this->maintenanceStatus; }
    public function getEolDate(): ?DateTimeImmutable { return $this->eolDate; }
    public function getVersionSyncedAt(): ?DateTimeImmutable { return $this->versionSyncedAt; }
    public function getLanguage(): Language { return $this->language; }
    public function getProject(): Project { return $this->project; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    public function updateVersionStatus(
        ?string $latestLts,
        ?string $ltsGap,
        ?string $maintenanceStatus,
        ?DateTimeImmutable $eolDate,
    ): void {
        $this->latestLts = $latestLts;
        $this->ltsGap = $ltsGap;
        $this->maintenanceStatus = $maintenanceStatus;
        $this->eolDate = $eolDate;
        $this->versionSyncedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();

        $this->recordEvent(new FrameworkVersionStatusUpdated(
            frameworkId: $this->id->toRfc4122(),
            projectId: $this->project->getId()->toRfc4122(),
            framework: $this->name,
            latestLts: $latestLts,
            maintenanceStatus: $maintenanceStatus,
        ));
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
```

---

## Tâche 4 — Repositories Language

**Fichiers créés**:
- `backend/src/Catalog/Domain/Repository/LanguageRepositoryInterface.php`
- `backend/src/Catalog/Infrastructure/Persistence/Doctrine/DoctrineLanguageRepository.php`

**Steps**:

- [ ] Créer l'interface

```php
<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Model\Language;
use Symfony\Component\Uid\Uuid;

interface LanguageRepositoryInterface
{
    public function findById(Uuid $id): ?Language;

    /** @return list<Language> */
    public function findAll(int $page = 1, int $perPage = 20): array;

    /** @return list<Language> */
    public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array;

    public function countByProjectId(Uuid $projectId): int;

    public function count(): int;

    public function save(Language $language): void;

    public function delete(Language $language): void;

    public function deleteByProjectId(Uuid $projectId): void;

    /** @return list<Language> */
    public function findByName(string $name): array;
}
```

- [ ] Créer l'implémentation Doctrine

```php
<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Model\Language;
use App\Catalog\Domain\Repository\LanguageRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineLanguageRepository implements LanguageRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function findById(Uuid $id): ?Language
    {
        return $this->em->find(Language::class, $id);
    }

    /** @return list<Language> */
    public function findAll(int $page = 1, int $perPage = 20): array
    {
        return $this->em->getRepository(Language::class)
            ->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    /** @return list<Language> */
    public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array
    {
        return $this->em->getRepository(Language::class)
            ->createQueryBuilder('l')
            ->where('l.project = :projectId')
            ->setParameter('projectId', $projectId, 'uuid')
            ->orderBy('l.name', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function countByProjectId(Uuid $projectId): int
    {
        return (int) $this->em->getRepository(Language::class)
            ->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.project = :projectId')
            ->setParameter('projectId', $projectId, 'uuid')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function count(): int
    {
        return (int) $this->em->getRepository(Language::class)
            ->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Language $language): void
    {
        $this->em->persist($language);
        $this->em->flush();
    }

    public function delete(Language $language): void
    {
        $this->em->remove($language);
        $this->em->flush();
    }

    public function deleteByProjectId(Uuid $projectId): void
    {
        $this->em->getRepository(Language::class)
            ->createQueryBuilder('l')
            ->delete()
            ->where('l.project = :projectId')
            ->setParameter('projectId', $projectId, 'uuid')
            ->getQuery()
            ->execute();
    }

    /** @return list<Language> */
    public function findByName(string $name): array
    {
        return $this->em->getRepository(Language::class)
            ->findBy(['name' => $name]);
    }
}
```

---

## Tâche 5 — Repositories Framework

**Fichiers créés**:
- `backend/src/Catalog/Domain/Repository/FrameworkRepositoryInterface.php`
- `backend/src/Catalog/Infrastructure/Persistence/Doctrine/DoctrineFrameworkRepository.php`

**Steps**:

- [ ] Créer l'interface

```php
<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Model\Framework;
use Symfony\Component\Uid\Uuid;

interface FrameworkRepositoryInterface
{
    public function findById(Uuid $id): ?Framework;

    /** @return list<Framework> */
    public function findAll(int $page = 1, int $perPage = 20): array;

    /** @return list<Framework> */
    public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array;

    public function countByProjectId(Uuid $projectId): int;

    public function count(): int;

    public function save(Framework $framework): void;

    public function delete(Framework $framework): void;

    public function deleteByProjectId(Uuid $projectId): void;

    /** @return list<Framework> */
    public function findByName(string $name): array;

    /** @return list<Framework> */
    public function findByLanguageId(Uuid $languageId): array;
}
```

- [ ] Créer l'implémentation Doctrine

```php
<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Domain\Model\Framework;
use App\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineFrameworkRepository implements FrameworkRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function findById(Uuid $id): ?Framework
    {
        return $this->em->find(Framework::class, $id);
    }

    /** @return list<Framework> */
    public function findAll(int $page = 1, int $perPage = 20): array
    {
        return $this->em->getRepository(Framework::class)
            ->createQueryBuilder('f')
            ->orderBy('f.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    /** @return list<Framework> */
    public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array
    {
        return $this->em->getRepository(Framework::class)
            ->createQueryBuilder('f')
            ->where('f.project = :projectId')
            ->setParameter('projectId', $projectId, 'uuid')
            ->orderBy('f.name', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function countByProjectId(Uuid $projectId): int
    {
        return (int) $this->em->getRepository(Framework::class)
            ->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.project = :projectId')
            ->setParameter('projectId', $projectId, 'uuid')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function count(): int
    {
        return (int) $this->em->getRepository(Framework::class)
            ->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Framework $framework): void
    {
        $this->em->persist($framework);
        $this->em->flush();
    }

    public function delete(Framework $framework): void
    {
        $this->em->remove($framework);
        $this->em->flush();
    }

    public function deleteByProjectId(Uuid $projectId): void
    {
        $this->em->getRepository(Framework::class)
            ->createQueryBuilder('f')
            ->delete()
            ->where('f.project = :projectId')
            ->setParameter('projectId', $projectId, 'uuid')
            ->getQuery()
            ->execute();
    }

    /** @return list<Framework> */
    public function findByName(string $name): array
    {
        return $this->em->getRepository(Framework::class)
            ->findBy(['name' => $name]);
    }

    /** @return list<Framework> */
    public function findByLanguageId(Uuid $languageId): array
    {
        return $this->em->getRepository(Framework::class)
            ->createQueryBuilder('f')
            ->where('f.language = :languageId')
            ->setParameter('languageId', $languageId, 'uuid')
            ->getQuery()
            ->getResult();
    }
}
```

---

## Tâche 6 — CRUD Language (Commands, Handlers, DTOs, Mapper, Controllers)

**Fichiers créés**:
- `backend/src/Catalog/Application/Command/CreateLanguageCommand.php`
- `backend/src/Catalog/Application/Command/DeleteLanguageCommand.php`
- `backend/src/Catalog/Application/Query/ListLanguagesQuery.php`
- `backend/src/Catalog/Application/Query/GetLanguageQuery.php`
- `backend/src/Catalog/Application/DTO/CreateLanguageInput.php`
- `backend/src/Catalog/Application/DTO/LanguageOutput.php`
- `backend/src/Catalog/Application/Mapper/LanguageMapper.php`
- `backend/src/Catalog/Application/CommandHandler/CreateLanguageHandler.php`
- `backend/src/Catalog/Application/CommandHandler/DeleteLanguageHandler.php`
- `backend/src/Catalog/Application/QueryHandler/ListLanguagesHandler.php`
- `backend/src/Catalog/Application/QueryHandler/GetLanguageHandler.php`
- `backend/src/Catalog/Presentation/Controller/CreateLanguageController.php`
- `backend/src/Catalog/Presentation/Controller/ListLanguagesController.php`
- `backend/src/Catalog/Presentation/Controller/GetLanguageController.php`
- `backend/src/Catalog/Presentation/Controller/DeleteLanguageController.php`

**Tests TDD** (écrire avant les handlers):
- `backend/tests/Unit/Catalog/Application/CommandHandler/CreateLanguageHandlerTest.php`
- `backend/tests/Unit/Catalog/Application/CommandHandler/DeleteLanguageHandlerTest.php`
- `backend/tests/Unit/Catalog/Application/QueryHandler/ListLanguagesHandlerTest.php`
- `backend/tests/Unit/Catalog/Application/QueryHandler/GetLanguageHandlerTest.php`

**Steps**:

- [ ] Créer DTO input

```php
<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateLanguageInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public string $name,
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 50)]
        public string $version,
        #[Assert\NotBlank]
        public string $detectedAt,
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $projectId,
    ) {
    }
}
```

- [ ] Créer DTO output

```php
<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

final readonly class LanguageOutput
{
    public function __construct(
        public string $id,
        public string $name,
        public string $version,
        public string $detectedAt,
        public string $projectId,
        public string $createdAt,
        public string $updatedAt,
        public ?string $eolDate = null,
        public ?string $maintenanceStatus = null,
    ) {
    }
}
```

- [ ] Créer mapper

```php
<?php

declare(strict_types=1);

namespace App\Catalog\Application\Mapper;

use App\Catalog\Application\DTO\LanguageOutput;
use App\Catalog\Domain\Model\Language;
use DateTimeInterface;

final class LanguageMapper
{
    public static function toOutput(Language $language): LanguageOutput
    {
        return new LanguageOutput(
            id: $language->getId()->toRfc4122(),
            name: $language->getName(),
            version: $language->getVersion(),
            detectedAt: $language->getDetectedAt()->format(DateTimeInterface::ATOM),
            projectId: $language->getProject()->getId()->toRfc4122(),
            createdAt: $language->getCreatedAt()->format(DateTimeInterface::ATOM),
            updatedAt: $language->getUpdatedAt()->format(DateTimeInterface::ATOM),
            eolDate: $language->getEolDate()?->format('Y-m-d'),
            maintenanceStatus: $language->getMaintenanceStatus(),
        );
    }
}
```

- [ ] Créer commandes et queries

```php
// CreateLanguageCommand.php
<?php
declare(strict_types=1);
namespace App\Catalog\Application\Command;
use App\Catalog\Application\DTO\CreateLanguageInput;
final readonly class CreateLanguageCommand
{
    public function __construct(public CreateLanguageInput $input) {}
}

// DeleteLanguageCommand.php
<?php
declare(strict_types=1);
namespace App\Catalog\Application\Command;
final readonly class DeleteLanguageCommand
{
    public function __construct(public string $id) {}
}

// ListLanguagesQuery.php
<?php
declare(strict_types=1);
namespace App\Catalog\Application\Query;
final readonly class ListLanguagesQuery
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
        public ?string $projectId = null,
    ) {}
}

// GetLanguageQuery.php
<?php
declare(strict_types=1);
namespace App\Catalog\Application\Query;
final readonly class GetLanguageQuery
{
    public function __construct(public string $id) {}
}
```

- [ ] Écrire les tests TDD pour les handlers

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog\Application\CommandHandler;

use App\Catalog\Application\Command\CreateLanguageCommand;
use App\Catalog\Application\CommandHandler\CreateLanguageHandler;
use App\Catalog\Application\DTO\CreateLanguageInput;
use App\Catalog\Application\DTO\LanguageOutput;
use App\Catalog\Domain\Model\Language;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Repository\LanguageRepositoryInterface;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Uid\Uuid;

describe('CreateLanguageHandler', function () {
    it('creates a language and returns output', function () {
        $projectId = Uuid::v7();
        $project = new class ($projectId) extends Project {
            public function __construct(private Uuid $fakeId)
            {
                parent::__construct();
            }
            #[\Override]
            public function getId(): Uuid { return $this->fakeId; }
        };

        $projectRepo = new class ($project) implements ProjectRepositoryInterface {
            public function __construct(private Project $project) {}
            public function findById(Uuid $id): ?Project { return $this->project; }
            // remaining methods throw \LogicException...
        };

        $languageRepo = new class implements LanguageRepositoryInterface {
            public ?Language $saved = null;
            public function findById(Uuid $id): ?Language { return null; }
            public function findAll(int $page = 1, int $perPage = 20): array { return []; }
            public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array { return []; }
            public function countByProjectId(Uuid $projectId): int { return 0; }
            public function count(): int { return 0; }
            public function save(Language $language): void { $this->saved = $language; }
            public function delete(Language $language): void {}
            public function deleteByProjectId(Uuid $projectId): void {}
            public function findByName(string $name): array { return []; }
        };

        $input = new CreateLanguageInput(
            name: 'PHP',
            version: '8.4',
            detectedAt: '2026-03-30T00:00:00+00:00',
            projectId: $projectId->toRfc4122(),
        );

        $handler = new CreateLanguageHandler($languageRepo, $projectRepo);
        $result = $handler(new CreateLanguageCommand($input));

        expect($result)->toBeInstanceOf(LanguageOutput::class)
            ->and($result->name)->toBe('PHP')
            ->and($result->version)->toBe('8.4')
            ->and($languageRepo->saved)->not->toBeNull();
    });

    it('throws NotFoundException when project does not exist', function () {
        $projectRepo = new class implements ProjectRepositoryInterface {
            public function findById(Uuid $id): ?Project { return null; }
            // remaining methods throw \LogicException...
        };
        $languageRepo = new class implements LanguageRepositoryInterface {
            public function findById(Uuid $id): ?Language { return null; }
            public function findAll(int $page = 1, int $perPage = 20): array { return []; }
            public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array { return []; }
            public function countByProjectId(Uuid $projectId): int { return 0; }
            public function count(): int { return 0; }
            public function save(Language $language): void {}
            public function delete(Language $language): void {}
            public function deleteByProjectId(Uuid $projectId): void {}
            public function findByName(string $name): array { return []; }
        };

        $handler = new CreateLanguageHandler($languageRepo, $projectRepo);

        expect(fn () => $handler(new CreateLanguageCommand(new CreateLanguageInput(
            name: 'PHP',
            version: '8.4',
            detectedAt: '2026-03-30T00:00:00+00:00',
            projectId: Uuid::v7()->toRfc4122(),
        ))))->toThrow(NotFoundException::class);
    });
});
```

- [ ] Créer les handlers

```php
// CreateLanguageHandler.php
<?php
declare(strict_types=1);
namespace App\Catalog\Application\CommandHandler;
use App\Catalog\Application\Command\CreateLanguageCommand;
use App\Catalog\Application\DTO\LanguageOutput;
use App\Catalog\Application\Mapper\LanguageMapper;
use App\Catalog\Domain\Model\Language;
use App\Catalog\Domain\Repository\LanguageRepositoryInterface;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateLanguageHandler
{
    public function __construct(
        private LanguageRepositoryInterface $languageRepository,
        private ProjectRepositoryInterface $projectRepository,
    ) {
    }

    public function __invoke(CreateLanguageCommand $command): LanguageOutput
    {
        $input = $command->input;

        $project = $this->projectRepository->findById(Uuid::fromString($input->projectId));
        if ($project === null) {
            throw NotFoundException::forEntity('Project', $input->projectId);
        }

        $language = Language::create(
            name: $input->name,
            version: $input->version,
            detectedAt: new DateTimeImmutable($input->detectedAt),
            project: $project,
        );

        $this->languageRepository->save($language);

        return LanguageMapper::toOutput($language);
    }
}
```

```php
// DeleteLanguageHandler.php
<?php
declare(strict_types=1);
namespace App\Catalog\Application\CommandHandler;
use App\Catalog\Application\Command\DeleteLanguageCommand;
use App\Catalog\Domain\Repository\LanguageRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class DeleteLanguageHandler
{
    public function __construct(
        private LanguageRepositoryInterface $languageRepository,
    ) {
    }

    public function __invoke(DeleteLanguageCommand $command): void
    {
        $language = $this->languageRepository->findById(Uuid::fromString($command->id));
        if ($language === null) {
            throw NotFoundException::forEntity('Language', $command->id);
        }

        $this->languageRepository->delete($language);
    }
}
```

```php
// ListLanguagesHandler.php
<?php
declare(strict_types=1);
namespace App\Catalog\Application\QueryHandler;
use App\Catalog\Application\DTO\LanguageOutput;
use App\Catalog\Application\Mapper\LanguageMapper;
use App\Catalog\Application\Query\ListLanguagesQuery;
use App\Catalog\Domain\Repository\LanguageRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListLanguagesHandler
{
    public function __construct(
        private LanguageRepositoryInterface $languageRepository,
    ) {
    }

    /** @return list<LanguageOutput> */
    public function __invoke(ListLanguagesQuery $query): array
    {
        $languages = $query->projectId !== null
            ? $this->languageRepository->findByProjectId(
                Uuid::fromString($query->projectId),
                $query->page,
                $query->perPage,
            )
            : $this->languageRepository->findAll($query->page, $query->perPage);

        return \array_map(LanguageMapper::toOutput(...), $languages);
    }
}
```

```php
// GetLanguageHandler.php
<?php
declare(strict_types=1);
namespace App\Catalog\Application\QueryHandler;
use App\Catalog\Application\DTO\LanguageOutput;
use App\Catalog\Application\Mapper\LanguageMapper;
use App\Catalog\Application\Query\GetLanguageQuery;
use App\Catalog\Domain\Repository\LanguageRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetLanguageHandler
{
    public function __construct(
        private LanguageRepositoryInterface $languageRepository,
    ) {
    }

    public function __invoke(GetLanguageQuery $query): LanguageOutput
    {
        $language = $this->languageRepository->findById(Uuid::fromString($query->id));
        if ($language === null) {
            throw NotFoundException::forEntity('Language', $query->id);
        }

        return LanguageMapper::toOutput($language);
    }
}
```

- [ ] Créer les controllers

```php
// CreateLanguageController.php
<?php
declare(strict_types=1);
namespace App\Catalog\Presentation\Controller;
use App\Catalog\Application\Command\CreateLanguageCommand;
use App\Catalog\Application\DTO\CreateLanguageInput;
use App\Shared\Application\DTO\ApiResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/catalog/languages', name: 'catalog_languages_create', methods: ['POST'])]
#[OA\Post(
    summary: 'Create a language',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: new Model(type: CreateLanguageInput::class)),
    ),
    tags: ['Catalog / Languages'],
    responses: [
        new OA\Response(response: 201, description: 'Language created'),
        new OA\Response(response: 422, description: 'Validation error'),
    ],
)]
final readonly class CreateLanguageController
{
    public function __construct(private MessageBusInterface $commandBus) {}

    public function __invoke(#[MapRequestPayload] CreateLanguageInput $input): JsonResponse
    {
        $envelope = $this->commandBus->dispatch(new CreateLanguageCommand($input));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray(), 201);
    }
}
```

```php
// ListLanguagesController.php
<?php
declare(strict_types=1);
namespace App\Catalog\Presentation\Controller;
use App\Catalog\Application\Query\ListLanguagesQuery;
use App\Shared\Application\DTO\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/catalog/languages', name: 'catalog_languages_list', methods: ['GET'])]
#[OA\Get(
    summary: 'List languages',
    tags: ['Catalog / Languages'],
    responses: [new OA\Response(response: 200, description: 'Languages list')],
)]
final readonly class ListLanguagesController
{
    public function __construct(private MessageBusInterface $queryBus) {}

    public function __invoke(Request $request): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new ListLanguagesQuery(
            page: (int) $request->query->get('page', 1),
            perPage: (int) $request->query->get('perPage', 20),
            projectId: $request->query->get('projectId'),
        ));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray());
    }
}
```

```php
// GetLanguageController.php
<?php
declare(strict_types=1);
namespace App\Catalog\Presentation\Controller;
use App\Catalog\Application\Query\GetLanguageQuery;
use App\Shared\Application\DTO\ApiResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/catalog/languages/{id}', name: 'catalog_languages_get', methods: ['GET'])]
#[OA\Get(
    summary: 'Get a language',
    tags: ['Catalog / Languages'],
    responses: [
        new OA\Response(response: 200, description: 'Language found'),
        new OA\Response(response: 404, description: 'Not found'),
    ],
)]
final readonly class GetLanguageController
{
    public function __construct(private MessageBusInterface $queryBus) {}

    public function __invoke(string $id): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetLanguageQuery($id));
        $result = $envelope->last(HandledStamp::class)?->getResult();

        return new JsonResponse(ApiResponse::success($result)->toArray());
    }
}
```

```php
// DeleteLanguageController.php
<?php
declare(strict_types=1);
namespace App\Catalog\Presentation\Controller;
use App\Catalog\Application\Command\DeleteLanguageCommand;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/catalog/languages/{id}', name: 'catalog_languages_delete', methods: ['DELETE'])]
#[OA\Delete(
    summary: 'Delete a language',
    tags: ['Catalog / Languages'],
    responses: [
        new OA\Response(response: 204, description: 'Language deleted'),
        new OA\Response(response: 404, description: 'Not found'),
    ],
)]
final readonly class DeleteLanguageController
{
    public function __construct(private MessageBusInterface $commandBus) {}

    public function __invoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteLanguageCommand($id));

        return new JsonResponse(null, 204);
    }
}
```

- [ ] Vérifier les tests

```bash
docker exec monark-backend-1 php vendor/bin/pest tests/Unit/Catalog/Application/CommandHandler/CreateLanguageHandlerTest.php tests/Unit/Catalog/Application/CommandHandler/DeleteLanguageHandlerTest.php tests/Unit/Catalog/Application/QueryHandler/ListLanguagesHandlerTest.php tests/Unit/Catalog/Application/QueryHandler/GetLanguageHandlerTest.php --no-coverage; echo "RC=$?"
```

---

## Tâche 7 — CRUD Framework (Commands, Handlers, DTOs, Mapper, Controllers)

**Fichiers créés**:
- `backend/src/Catalog/Application/Command/CreateFrameworkCommand.php`
- `backend/src/Catalog/Application/Command/DeleteFrameworkCommand.php`
- `backend/src/Catalog/Application/Query/ListFrameworksQuery.php`
- `backend/src/Catalog/Application/Query/GetFrameworkQuery.php`
- `backend/src/Catalog/Application/DTO/CreateFrameworkInput.php`
- `backend/src/Catalog/Application/DTO/FrameworkOutput.php`
- `backend/src/Catalog/Application/Mapper/FrameworkMapper.php`
- `backend/src/Catalog/Application/CommandHandler/CreateFrameworkHandler.php`
- `backend/src/Catalog/Application/CommandHandler/DeleteFrameworkHandler.php`
- `backend/src/Catalog/Application/QueryHandler/ListFrameworksHandler.php`
- `backend/src/Catalog/Application/QueryHandler/GetFrameworkHandler.php`
- `backend/src/Catalog/Presentation/Controller/CreateFrameworkController.php`
- `backend/src/Catalog/Presentation/Controller/ListFrameworksController.php`
- `backend/src/Catalog/Presentation/Controller/GetFrameworkController.php`
- `backend/src/Catalog/Presentation/Controller/DeleteFrameworkController.php`

**Tests TDD**:
- `backend/tests/Unit/Catalog/Application/CommandHandler/CreateFrameworkHandlerTest.php`
- `backend/tests/Unit/Catalog/Application/CommandHandler/DeleteFrameworkHandlerTest.php`
- `backend/tests/Unit/Catalog/Application/QueryHandler/ListFrameworksHandlerTest.php`
- `backend/tests/Unit/Catalog/Application/QueryHandler/GetFrameworkHandlerTest.php`

**Steps**:

- [ ] Créer DTO input

```php
<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateFrameworkInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public string $name,
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 50)]
        public string $version,
        #[Assert\NotBlank]
        public string $detectedAt,
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $languageId,
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $projectId,
    ) {
    }
}
```

- [ ] Créer DTO output

```php
<?php

declare(strict_types=1);

namespace App\Catalog\Application\DTO;

final readonly class FrameworkOutput
{
    public function __construct(
        public string $id,
        public string $name,
        public string $version,
        public string $detectedAt,
        public string $languageId,
        public string $languageName,
        public string $projectId,
        public string $createdAt,
        public string $updatedAt,
        public ?string $latestLts = null,
        public ?string $ltsGap = null,
        public ?string $maintenanceStatus = null,
        public ?string $eolDate = null,
        public ?string $versionSyncedAt = null,
    ) {
    }
}
```

- [ ] Créer mapper

```php
<?php

declare(strict_types=1);

namespace App\Catalog\Application\Mapper;

use App\Catalog\Application\DTO\FrameworkOutput;
use App\Catalog\Domain\Model\Framework;
use DateTimeInterface;

final class FrameworkMapper
{
    public static function toOutput(Framework $framework): FrameworkOutput
    {
        return new FrameworkOutput(
            id: $framework->getId()->toRfc4122(),
            name: $framework->getName(),
            version: $framework->getVersion(),
            detectedAt: $framework->getDetectedAt()->format(DateTimeInterface::ATOM),
            languageId: $framework->getLanguage()->getId()->toRfc4122(),
            languageName: $framework->getLanguage()->getName(),
            projectId: $framework->getProject()->getId()->toRfc4122(),
            createdAt: $framework->getCreatedAt()->format(DateTimeInterface::ATOM),
            updatedAt: $framework->getUpdatedAt()->format(DateTimeInterface::ATOM),
            latestLts: $framework->getLatestLts(),
            ltsGap: $framework->getLtsGap(),
            maintenanceStatus: $framework->getMaintenanceStatus(),
            eolDate: $framework->getEolDate()?->format('Y-m-d'),
            versionSyncedAt: $framework->getVersionSyncedAt()?->format(DateTimeInterface::ATOM),
        );
    }
}
```

- [ ] Créer commandes, queries et handlers (même pattern que Language — adapté pour Framework avec `languageId`)

```php
// CreateFrameworkHandler.php
<?php
declare(strict_types=1);
namespace App\Catalog\Application\CommandHandler;
use App\Catalog\Application\Command\CreateFrameworkCommand;
use App\Catalog\Application\DTO\FrameworkOutput;
use App\Catalog\Application\Mapper\FrameworkMapper;
use App\Catalog\Domain\Model\Framework;
use App\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use App\Catalog\Domain\Repository\LanguageRepositoryInterface;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateFrameworkHandler
{
    public function __construct(
        private FrameworkRepositoryInterface $frameworkRepository,
        private LanguageRepositoryInterface $languageRepository,
        private ProjectRepositoryInterface $projectRepository,
    ) {
    }

    public function __invoke(CreateFrameworkCommand $command): FrameworkOutput
    {
        $input = $command->input;

        $language = $this->languageRepository->findById(Uuid::fromString($input->languageId));
        if ($language === null) {
            throw NotFoundException::forEntity('Language', $input->languageId);
        }

        $project = $this->projectRepository->findById(Uuid::fromString($input->projectId));
        if ($project === null) {
            throw NotFoundException::forEntity('Project', $input->projectId);
        }

        $framework = Framework::create(
            name: $input->name,
            version: $input->version,
            detectedAt: new DateTimeImmutable($input->detectedAt),
            language: $language,
            project: $project,
        );

        $this->frameworkRepository->save($framework);

        return FrameworkMapper::toOutput($framework);
    }
}
```

```php
// DeleteFrameworkHandler.php
<?php
declare(strict_types=1);
namespace App\Catalog\Application\CommandHandler;
use App\Catalog\Application\Command\DeleteFrameworkCommand;
use App\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class DeleteFrameworkHandler
{
    public function __construct(private FrameworkRepositoryInterface $frameworkRepository) {}

    public function __invoke(DeleteFrameworkCommand $command): void
    {
        $framework = $this->frameworkRepository->findById(Uuid::fromString($command->id));
        if ($framework === null) {
            throw NotFoundException::forEntity('Framework', $command->id);
        }
        $this->frameworkRepository->delete($framework);
    }
}
```

```php
// ListFrameworksHandler.php
<?php
declare(strict_types=1);
namespace App\Catalog\Application\QueryHandler;
use App\Catalog\Application\DTO\FrameworkOutput;
use App\Catalog\Application\Mapper\FrameworkMapper;
use App\Catalog\Application\Query\ListFrameworksQuery;
use App\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class ListFrameworksHandler
{
    public function __construct(private FrameworkRepositoryInterface $frameworkRepository) {}

    /** @return list<FrameworkOutput> */
    public function __invoke(ListFrameworksQuery $query): array
    {
        $frameworks = $query->projectId !== null
            ? $this->frameworkRepository->findByProjectId(
                Uuid::fromString($query->projectId),
                $query->page,
                $query->perPage,
            )
            : $this->frameworkRepository->findAll($query->page, $query->perPage);

        return \array_map(FrameworkMapper::toOutput(...), $frameworks);
    }
}
```

```php
// GetFrameworkHandler.php
<?php
declare(strict_types=1);
namespace App\Catalog\Application\QueryHandler;
use App\Catalog\Application\DTO\FrameworkOutput;
use App\Catalog\Application\Mapper\FrameworkMapper;
use App\Catalog\Application\Query\GetFrameworkQuery;
use App\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use App\Shared\Domain\Exception\NotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
final readonly class GetFrameworkHandler
{
    public function __construct(private FrameworkRepositoryInterface $frameworkRepository) {}

    public function __invoke(GetFrameworkQuery $query): FrameworkOutput
    {
        $framework = $this->frameworkRepository->findById(Uuid::fromString($query->id));
        if ($framework === null) {
            throw NotFoundException::forEntity('Framework', $query->id);
        }
        return FrameworkMapper::toOutput($framework);
    }
}
```

- [ ] Créer les 4 controllers (même pattern que Language — routes `/api/v1/catalog/frameworks`)

- [ ] Vérifier les tests

```bash
docker exec monark-backend-1 php vendor/bin/pest tests/Unit/Catalog/Application/CommandHandler/CreateFrameworkHandlerTest.php tests/Unit/Catalog/Application/CommandHandler/DeleteFrameworkHandlerTest.php tests/Unit/Catalog/Application/QueryHandler/ListFrameworksHandlerTest.php tests/Unit/Catalog/Application/QueryHandler/GetFrameworkHandlerTest.php --no-coverage; echo "RC=$?"
```

---

## Tâche 8 — FrameworkVersionStatusUpdater

**Fichiers créés**:
- `backend/src/Catalog/Application/Service/FrameworkVersionStatusUpdater.php`

**Tests TDD**:
- `backend/tests/Unit/Catalog/Application/Service/FrameworkVersionStatusUpdaterTest.php`

**Steps**:

- [ ] Écrire le test

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog\Application\Service;

use App\Catalog\Application\Service\FrameworkVersionStatusUpdater;
use App\Catalog\Domain\Model\Framework;
use App\Catalog\Domain\Model\Language;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use App\VersionRegistry\Domain\Model\ProductVersion;
use App\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use App\VersionRegistry\Domain\Repository\ProductVersionRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

describe('FrameworkVersionStatusUpdater', function () {
    it('returns 0 when framework is not in the map', function () {
        $frameworkRepo = new class implements FrameworkRepositoryInterface {
            public function findById(Uuid $id): ?Framework { return null; }
            public function findAll(int $page = 1, int $perPage = 20): array { return []; }
            public function findByProjectId(Uuid $projectId, int $page = 1, int $perPage = 20): array { return []; }
            public function countByProjectId(Uuid $projectId): int { return 0; }
            public function count(): int { return 0; }
            public function save(Framework $framework): void {}
            public function delete(Framework $framework): void {}
            public function deleteByProjectId(Uuid $projectId): void {}
            public function findByName(string $name): array { return []; }
            public function findByLanguageId(Uuid $languageId): array { return []; }
        };

        $productVersionRepo = new class implements ProductVersionRepositoryInterface {
            public function findByNameAndManager(string $name, ?string $manager): array { return []; }
        };
        $productRepo = new class implements ProductRepositoryInterface {
            public function findByNameAndManager(string $name, ?string $manager): ?\App\VersionRegistry\Domain\Model\Product { return null; }
        };
        $eventBus = new class implements MessageBusInterface {
            public function dispatch(object $message, array $stamps = []): \Symfony\Component\Messenger\Envelope { return new \Symfony\Component\Messenger\Envelope($message); }
        };

        $updater = new FrameworkVersionStatusUpdater($frameworkRepo, $productVersionRepo, $productRepo, $eventBus);

        $project = \Mockery::mock(Project::class);
        $project->allows('getId')->andReturn(Uuid::v7());

        $language = \Mockery::mock(Language::class);
        $language->allows('getId')->andReturn(Uuid::v7());
        $language->allows('getName')->andReturn('PHP');

        $framework = \Mockery::mock(Framework::class);
        $framework->allows('getName')->andReturn('UnknownFramework');

        expect($updater->refreshAll([$framework]))->toBe(0);
    });
});
```

- [ ] Créer le service (renommage/adaptation de TechStackVersionStatusUpdater)

```php
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

        foreach ($allVersions as $pv) {
            try {
                $pvParsed = SemanticVersion::parse($pv->getVersion());
            } catch (InvalidArgumentException) {
                continue;
            }

            if ($pvParsed->major === $current->major && $pv->getEolDate() !== null) {
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
```

- [ ] Vérifier les tests

```bash
docker exec monark-backend-1 php vendor/bin/pest tests/Unit/Catalog/Application/Service/FrameworkVersionStatusUpdaterTest.php --no-coverage; echo "RC=$?"
```

---

## Tâche 9 — Adaptation ScanProjectHandler

**Fichiers modifiés**:
- `backend/src/Catalog/Application/CommandHandler/ScanProjectHandler.php`
- `backend/tests/Unit/Catalog/Application/CommandHandler/ScanProjectHandlerTest.php`

**Steps**:

- [ ] Mettre à jour le test en premier

Le test doit vérifier que le handler :
1. Appelle `languageRepository->deleteByProjectId()` au lieu de `techStackRepository->deleteByProjectId()`
2. Crée un `Language` et un `Framework` par stack détectée
3. Dispatch `ProjectScannedEvent`

- [ ] Modifier `ScanProjectHandler.php`

Remplacer les dépendances `TechStackRepositoryInterface` par `LanguageRepositoryInterface` + `FrameworkRepositoryInterface` :

```php
<?php

declare(strict_types=1);

namespace App\Catalog\Application\CommandHandler;

use App\Catalog\Application\Command\ScanProjectCommand;
use App\Catalog\Application\DTO\ScanResultOutput;
use App\Catalog\Domain\Model\Framework;
use App\Catalog\Domain\Model\Language;
use App\Catalog\Domain\Port\ProjectScannerInterface;
use App\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use App\Catalog\Domain\Repository\LanguageRepositoryInterface;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Shared\Domain\Event\ProjectScannedEvent;
use App\Shared\Domain\Exception\NotFoundException;
use App\Shared\Domain\Port\DependencyWriterPort;
use DateTimeImmutable;
use DomainException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'command.bus')]
readonly class ScanProjectHandler
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository,
        private LanguageRepositoryInterface $languageRepository,
        private FrameworkRepositoryInterface $frameworkRepository,
        private DependencyWriterPort $dependencyWriter,
        private ProjectScannerInterface $projectScanner,
        private MessageBusInterface $eventBus,
    ) {
    }

    public function __invoke(ScanProjectCommand $command): ScanResultOutput
    {
        $project = $this->projectRepository->findById(Uuid::fromString($command->projectId));
        if ($project === null) {
            throw NotFoundException::forEntity('Project', $command->projectId);
        }

        if ($project->getProvider() === null || $project->getExternalId() === null) {
            throw new DomainException(\sprintf('Project "%s" is not linked to a provider.', $command->projectId));
        }

        $scanResult = $this->projectScanner->scan($project);

        if ($scanResult->stacks === [] && $scanResult->dependencies === []) {
            return new ScanResultOutput(
                stacksDetected: 0,
                dependenciesDetected: 0,
                stacks: [],
                dependencies: [],
            );
        }

        $projectId = $project->getId();
        $this->languageRepository->deleteByProjectId($projectId);
        $this->frameworkRepository->deleteByProjectId($projectId);

        $stackOutputs = [];
        foreach ($scanResult->stacks as $detected) {
            if ($detected->framework === 'none') {
                continue;
            }

            $language = Language::create(
                name: $detected->language,
                version: $detected->version,
                detectedAt: new DateTimeImmutable(),
                project: $project,
            );
            $this->languageRepository->save($language);

            $framework = Framework::create(
                name: $detected->framework,
                version: $detected->frameworkVersion,
                detectedAt: new DateTimeImmutable(),
                language: $language,
                project: $project,
            );
            $this->frameworkRepository->save($framework);

            $stackOutputs[] = [
                'language' => $detected->language,
                'framework' => $detected->framework,
                'version' => $detected->version,
                'frameworkVersion' => $detected->frameworkVersion,
            ];
        }

        $depOutputs = [];
        $scannedDeps = [];
        foreach ($scanResult->dependencies as $detected) {
            $this->dependencyWriter->upsertFromScan(
                name: $detected->name,
                currentVersion: $detected->currentVersion,
                packageManager: $detected->packageManager->value,
                type: $detected->type->value,
                projectId: $project->getId(),
                repositoryUrl: $detected->repositoryUrl,
            );
            $scannedDeps[] = [
                'name' => $detected->name,
                'packageManager' => $detected->packageManager->value,
            ];
            $depOutputs[] = [
                'name' => $detected->name,
                'version' => $detected->currentVersion,
                'packageManager' => $detected->packageManager->value,
                'type' => $detected->type->value,
            ];
        }

        $this->dependencyWriter->removeStaleByProjectId($projectId, $scannedDeps);

        $this->eventBus->dispatch(new ProjectScannedEvent(
            projectId: $command->projectId,
            scanResult: $scanResult,
        ));

        return new ScanResultOutput(
            stacksDetected: \count($stackOutputs),
            dependenciesDetected: \count($depOutputs),
            stacks: $stackOutputs,
            dependencies: $depOutputs,
        );
    }
}
```

- [ ] Vérifier les tests

```bash
docker exec monark-backend-1 php vendor/bin/pest tests/Unit/Catalog/Application/CommandHandler/ScanProjectHandlerTest.php --no-coverage; echo "RC=$?"
```

---

## Tâche 10 — Event Listeners adaptés

**Fichiers modifiés**:
- `backend/src/Catalog/Application/EventListener/UpdateTechStackVersionStatusListener.php` → adapté pour Framework
- `backend/src/Catalog/Application/EventListener/RefreshTechStackStatusOnScanListener.php` → adapté pour Framework

**Tests modifiés**:
- `backend/tests/Unit/Catalog/Application/EventListener/UpdateTechStackVersionStatusListenerTest.php`

**Steps**:

- [ ] Modifier `UpdateTechStackVersionStatusListener.php`

```php
<?php

declare(strict_types=1);

namespace App\Catalog\Application\EventListener;

use App\Catalog\Application\Service\FrameworkVersionStatusUpdater;
use App\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use App\Shared\Domain\Event\ProductVersionsSyncedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class UpdateTechStackVersionStatusListener
{
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
        private FrameworkRepositoryInterface $frameworkRepository,
        private FrameworkVersionStatusUpdater $updater,
    ) {
    }

    public function __invoke(ProductVersionsSyncedEvent $event): void
    {
        if ($event->packageManager !== null) {
            return;
        }

        $frameworkName = self::FRAMEWORK_REVERSE_MAP[$event->productName] ?? null;
        if ($frameworkName === null) {
            return;
        }

        $frameworks = $this->frameworkRepository->findByName($frameworkName);
        $this->updater->refreshAll($frameworks);
    }
}
```

- [ ] Modifier `RefreshTechStackStatusOnScanListener.php`

```php
<?php

declare(strict_types=1);

namespace App\Catalog\Application\EventListener;

use App\Catalog\Application\Service\FrameworkVersionStatusUpdater;
use App\Catalog\Domain\Repository\FrameworkRepositoryInterface;
use App\Shared\Domain\Event\ProjectScannedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class RefreshTechStackStatusOnScanListener
{
    public function __construct(
        private FrameworkRepositoryInterface $frameworkRepository,
        private FrameworkVersionStatusUpdater $updater,
    ) {
    }

    public function __invoke(ProjectScannedEvent $event): void
    {
        $frameworks = $this->frameworkRepository->findByProjectId(
            Uuid::fromString($event->projectId),
            1,
            1000,
        );

        $this->updater->refreshAll($frameworks);
    }
}
```

- [ ] Mettre à jour les tests des listeners

```bash
docker exec monark-backend-1 php vendor/bin/pest tests/Unit/Catalog/Application/EventListener/ --no-coverage; echo "RC=$?"
```

---

## Tâche 11 — Cleanup TechStack

**Fichiers supprimés**:
- `backend/src/Catalog/Domain/Model/TechStack.php`
- `backend/src/Catalog/Domain/Repository/TechStackRepositoryInterface.php`
- `backend/src/Catalog/Domain/Event/TechStackVersionStatusUpdated.php`
- `backend/src/Catalog/Domain/ValueObject/TechStackHealth.php`
- `backend/src/Catalog/Domain/Service/TechStackHealthCalculator.php`
- `backend/src/Catalog/Application/Command/CreateTechStackCommand.php`
- `backend/src/Catalog/Application/Command/DeleteTechStackCommand.php`
- `backend/src/Catalog/Application/Query/GetTechStackQuery.php`
- `backend/src/Catalog/Application/Query/ListTechStacksQuery.php`
- `backend/src/Catalog/Application/DTO/CreateTechStackInput.php`
- `backend/src/Catalog/Application/DTO/TechStackOutput.php`
- `backend/src/Catalog/Application/DTO/TechStackListOutput.php`
- `backend/src/Catalog/Application/DTO/TechStackSummaryDTO.php`
- `backend/src/Catalog/Application/Mapper/TechStackMapper.php`
- `backend/src/Catalog/Application/Service/TechStackVersionStatusUpdater.php`
- `backend/src/Catalog/Application/CommandHandler/CreateTechStackHandler.php`
- `backend/src/Catalog/Application/CommandHandler/DeleteTechStackHandler.php`
- `backend/src/Catalog/Application/QueryHandler/ListTechStacksHandler.php`
- `backend/src/Catalog/Application/QueryHandler/GetTechStackHandler.php`
- `backend/src/Catalog/Infrastructure/Persistence/Doctrine/DoctrineTechStackRepository.php`
- `backend/src/Catalog/Presentation/Controller/CreateTechStackController.php`
- `backend/src/Catalog/Presentation/Controller/ListTechStacksController.php`
- `backend/src/Catalog/Presentation/Controller/GetTechStackController.php`
- `backend/src/Catalog/Presentation/Controller/DeleteTechStackController.php`
- `backend/tests/Unit/Catalog/Domain/Model/TechStackTest.php`
- `backend/tests/Unit/Catalog/Domain/ValueObject/TechStackHealthTest.php`
- `backend/tests/Unit/Catalog/Domain/Service/TechStackHealthCalculatorTest.php`
- `backend/tests/Unit/Catalog/Application/CommandHandler/CreateTechStackHandlerTest.php`
- `backend/tests/Unit/Catalog/Application/CommandHandler/DeleteTechStackHandlerTest.php`
- `backend/tests/Unit/Catalog/Application/QueryHandler/ListTechStacksHandlerTest.php`
- `backend/tests/Unit/Catalog/Application/QueryHandler/GetTechStackHandlerTest.php`
- `backend/tests/Unit/Catalog/Application/Service/TechStackVersionStatusUpdaterTest.php`
- `backend/tests/Unit/Catalog/Presentation/Controller/ResourceControllersTest.php` (à vérifier : contient des tests TechStack controllers)

**Steps**:

- [ ] Supprimer tous les fichiers TechStack

```bash
rm backend/src/Catalog/Domain/Model/TechStack.php \
   backend/src/Catalog/Domain/Repository/TechStackRepositoryInterface.php \
   backend/src/Catalog/Domain/Event/TechStackVersionStatusUpdated.php \
   backend/src/Catalog/Domain/ValueObject/TechStackHealth.php \
   backend/src/Catalog/Domain/Service/TechStackHealthCalculator.php \
   backend/src/Catalog/Application/Command/CreateTechStackCommand.php \
   backend/src/Catalog/Application/Command/DeleteTechStackCommand.php \
   backend/src/Catalog/Application/Query/GetTechStackQuery.php \
   backend/src/Catalog/Application/Query/ListTechStacksQuery.php \
   backend/src/Catalog/Application/DTO/CreateTechStackInput.php \
   backend/src/Catalog/Application/DTO/TechStackOutput.php \
   backend/src/Catalog/Application/DTO/TechStackListOutput.php \
   backend/src/Catalog/Application/DTO/TechStackSummaryDTO.php \
   backend/src/Catalog/Application/Mapper/TechStackMapper.php \
   backend/src/Catalog/Application/Service/TechStackVersionStatusUpdater.php \
   backend/src/Catalog/Application/CommandHandler/CreateTechStackHandler.php \
   backend/src/Catalog/Application/CommandHandler/DeleteTechStackHandler.php \
   backend/src/Catalog/Application/QueryHandler/ListTechStacksHandler.php \
   backend/src/Catalog/Application/QueryHandler/GetTechStackHandler.php \
   backend/src/Catalog/Infrastructure/Persistence/Doctrine/DoctrineTechStackRepository.php \
   backend/src/Catalog/Presentation/Controller/CreateTechStackController.php \
   backend/src/Catalog/Presentation/Controller/ListTechStacksController.php \
   backend/src/Catalog/Presentation/Controller/GetTechStackController.php \
   backend/src/Catalog/Presentation/Controller/DeleteTechStackController.php \
   backend/tests/Unit/Catalog/Domain/Model/TechStackTest.php \
   backend/tests/Unit/Catalog/Domain/ValueObject/TechStackHealthTest.php \
   backend/tests/Unit/Catalog/Domain/Service/TechStackHealthCalculatorTest.php \
   backend/tests/Unit/Catalog/Application/CommandHandler/CreateTechStackHandlerTest.php \
   backend/tests/Unit/Catalog/Application/CommandHandler/DeleteTechStackHandlerTest.php \
   backend/tests/Unit/Catalog/Application/QueryHandler/ListTechStacksHandlerTest.php \
   backend/tests/Unit/Catalog/Application/QueryHandler/GetTechStackHandlerTest.php \
   backend/tests/Unit/Catalog/Application/Service/TechStackVersionStatusUpdaterTest.php
```

- [ ] Vérifier `ResourceControllersTest.php` — supprimer ou adapter les tests TechStack controllers qui s'y trouvent

---

## Tâche 12 — Vérification finale

**Steps**:

- [ ] Suite complète des tests

```bash
docker exec monark-backend-1 php vendor/bin/pest tests/Unit/Catalog/ --no-coverage; echo "RC=$?"
```

- [ ] PHPStan

```bash
docker exec monark-backend-1 php vendor/bin/phpstan analyse src/Catalog; echo "RC=$?"
```

- [ ] Vider le cache Symfony

```bash
docker exec monark-backend-1 php bin/console cache:clear; echo "RC=$?"
```

- [ ] Vérifier que le container démarre proprement (warmup Doctrine)

```bash
docker exec monark-backend-1 php bin/console doctrine:mapping:info | grep -E "Language|Framework"; echo "RC=$?"
```

- [ ] Suite complète des tests backend

```bash
docker exec monark-backend-1 php vendor/bin/pest --no-coverage; echo "RC=$?"
```

---

## Fichiers à créer — récapitulatif

| Fichier | Action |
|---|---|
| `migrations/Version20260330100000.php` | CREATE |
| `Domain/Model/Language.php` | CREATE |
| `Domain/Model/Framework.php` | CREATE |
| `Domain/Event/LanguageStatusUpdated.php` | CREATE |
| `Domain/Event/FrameworkVersionStatusUpdated.php` | CREATE |
| `Domain/ValueObject/FrameworkHealth.php` | CREATE |
| `Domain/Service/FrameworkHealthCalculator.php` | CREATE |
| `Domain/Repository/LanguageRepositoryInterface.php` | CREATE |
| `Domain/Repository/FrameworkRepositoryInterface.php` | CREATE |
| `Infrastructure/Persistence/Doctrine/DoctrineLanguageRepository.php` | CREATE |
| `Infrastructure/Persistence/Doctrine/DoctrineFrameworkRepository.php` | CREATE |
| `Application/Command/CreateLanguageCommand.php` | CREATE |
| `Application/Command/DeleteLanguageCommand.php` | CREATE |
| `Application/Command/CreateFrameworkCommand.php` | CREATE |
| `Application/Command/DeleteFrameworkCommand.php` | CREATE |
| `Application/Query/ListLanguagesQuery.php` | CREATE |
| `Application/Query/GetLanguageQuery.php` | CREATE |
| `Application/Query/ListFrameworksQuery.php` | CREATE |
| `Application/Query/GetFrameworkQuery.php` | CREATE |
| `Application/DTO/CreateLanguageInput.php` | CREATE |
| `Application/DTO/LanguageOutput.php` | CREATE |
| `Application/DTO/CreateFrameworkInput.php` | CREATE |
| `Application/DTO/FrameworkOutput.php` | CREATE |
| `Application/Mapper/LanguageMapper.php` | CREATE |
| `Application/Mapper/FrameworkMapper.php` | CREATE |
| `Application/CommandHandler/CreateLanguageHandler.php` | CREATE |
| `Application/CommandHandler/DeleteLanguageHandler.php` | CREATE |
| `Application/CommandHandler/CreateFrameworkHandler.php` | CREATE |
| `Application/CommandHandler/DeleteFrameworkHandler.php` | CREATE |
| `Application/QueryHandler/ListLanguagesHandler.php` | CREATE |
| `Application/QueryHandler/GetLanguageHandler.php` | CREATE |
| `Application/QueryHandler/ListFrameworksHandler.php` | CREATE |
| `Application/QueryHandler/GetFrameworkHandler.php` | CREATE |
| `Application/Service/FrameworkVersionStatusUpdater.php` | CREATE |
| `Presentation/Controller/CreateLanguageController.php` | CREATE |
| `Presentation/Controller/ListLanguagesController.php` | CREATE |
| `Presentation/Controller/GetLanguageController.php` | CREATE |
| `Presentation/Controller/DeleteLanguageController.php` | CREATE |
| `Presentation/Controller/CreateFrameworkController.php` | CREATE |
| `Presentation/Controller/ListFrameworksController.php` | CREATE |
| `Presentation/Controller/GetFrameworkController.php` | CREATE |
| `Presentation/Controller/DeleteFrameworkController.php` | CREATE |
| `Application/CommandHandler/ScanProjectHandler.php` | MODIFY |
| `Application/EventListener/UpdateTechStackVersionStatusListener.php` | MODIFY |
| `Application/EventListener/RefreshTechStackStatusOnScanListener.php` | MODIFY |
| Tous les fichiers `TechStack*` | DELETE (voir Tâche 11) |
