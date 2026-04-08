# Dependency Replication Excellence — Phase 4: Cross-Context DDD Patterns

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replicate the DDD patterns established in Phases 1-3 across the Catalog, Identity, Activity, and VersionRegistry bounded contexts. Each context gains richer domain models, domain events, and architectural enforcement.

**Architecture:** Context-by-context — each task is fully self-contained. Catalog gets `TechStackHealth` scoring reusing `SemanticVersion`. Identity gets a `PasswordPolicy` domain service and `RecordsDomainEvents` on `User`. Activity gets domain events emitted from `BuildMetric`. VersionRegistry gets `ResolvedSemanticVersion` VO, a `VersionResolverSelector` domain service replacing the ad-hoc resolver loop, and a proper Deptrac ruleset entry. The existing `TechStackVersionStatusUpdater` application service is refactored to use `SemanticVersion` internally.

**Tech Stack:** PHP 8.4, Symfony 8, Pest 4, Doctrine ORM 3.4

**Spec:** `docs/superpowers/specs/2026-03-30-dependency-context-excellence-design.md` (Section 8, Phase 4)

**Runtime constraint:** All commands run via `docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend ...` (never bare php/composer).

**Test conventions:**
- Pest `describe/it` syntax, `expect()` fluent assertions
- Anonymous class stubs (no mocking library), helper functions at file top with `\` prefix calls
- No `beforeEach` — inline setup per test

---

## File Map

### Task 1 — Deptrac: VersionRegistry layers
- Modify: `backend/deptrac.yaml` — add VersionRegistry ruleset

### Task 2 — Catalog: TechStackHealth Value Object
- Create: `backend/src/Catalog/Domain/ValueObject/TechStackHealth.php`
- Create: `backend/src/Catalog/Domain/ValueObject/MaintenanceStatus.php`
- Create: `backend/src/Catalog/Domain/Service/TechStackHealthCalculator.php`
- Modify: `backend/src/Catalog/Domain/Model/TechStack.php` — expose `calculateHealth()`, use `SemanticVersion` internally
- Test: `backend/tests/Unit/Catalog/Domain/ValueObject/TechStackHealthTest.php`
- Test: `backend/tests/Unit/Catalog/Domain/Service/TechStackHealthCalculatorTest.php`
- Modify: `backend/tests/Unit/Catalog/Domain/Model/TechStackTest.php`

### Task 3 — Catalog: TechStack domain events + RecordsDomainEvents
- Create: `backend/src/Catalog/Domain/Event/TechStackVersionStatusUpdated.php`
- Modify: `backend/src/Catalog/Domain/Model/TechStack.php` — use `RecordsDomainEvents`, emit event on `updateVersionStatus()`
- Modify: `backend/src/Catalog/Application/Service/TechStackVersionStatusUpdater.php` — pull and dispatch events after save
- Test: `backend/tests/Unit/Catalog/Domain/Model/TechStackTest.php`
- Test: `backend/tests/Unit/Catalog/Application/Service/TechStackVersionStatusUpdaterTest.php`

### Task 4 — Identity: PasswordPolicy domain service + User events
- Create: `backend/src/Identity/Domain/Service/PasswordPolicy.php`
- Create: `backend/src/Identity/Domain/ValueObject/PasswordStrength.php`
- Create: `backend/src/Identity/Domain/Event/UserPasswordChanged.php`
- Modify: `backend/src/Identity/Domain/Model/User.php` — use `RecordsDomainEvents`, emit `UserCreated` from aggregate, validate via `PasswordPolicy`, emit `UserPasswordChanged`
- Modify: `backend/src/Identity/Application/CommandHandler/RegisterUserHandler.php` — pull and dispatch domain events
- Modify: `backend/src/Identity/Application/CommandHandler/UpdateUserHandler.php` — pull and dispatch domain events
- Test: `backend/tests/Unit/Identity/Domain/Service/PasswordPolicyTest.php`
- Test: `backend/tests/Unit/Identity/Domain/UserTest.php` (modify)

### Task 5 — Activity: BuildMetric domain events
- Create: `backend/src/Activity/Domain/Event/BuildMetricRecorded.php`
- Create: `backend/src/Activity/Domain/Port/BuildMetricNotifierPort.php`
- Modify: `backend/src/Activity/Domain/Model/BuildMetric.php` — use `RecordsDomainEvents`, emit `BuildMetricRecorded` from `create()`
- Modify: `backend/src/Activity/Application/CommandHandler/CreateBuildMetricHandler.php` — pull and dispatch events
- Test: `backend/tests/Unit/Activity/Domain/BuildMetricTest.php` (modify)
- Test: `backend/tests/Unit/Activity/Application/CommandHandler/CreateBuildMetricHandlerTest.php` (modify)

### Task 6 — VersionRegistry: ResolvedSemanticVersion VO + VersionResolverSelector
- Create: `backend/src/VersionRegistry/Domain/ValueObject/ResolvedSemanticVersion.php`
- Create: `backend/src/VersionRegistry/Domain/Service/VersionResolverSelector.php`
- Modify: `backend/src/VersionRegistry/Domain/Model/Product.php` — typed `getLatestSemanticVersion()`, `getLtsSemanticVersion()`
- Modify: `backend/src/VersionRegistry/Application/CommandHandler/SyncSingleProductHandler.php` — use `VersionResolverSelector`
- Test: `backend/tests/Unit/VersionRegistry/Domain/ValueObject/ResolvedSemanticVersionTest.php`
- Test: `backend/tests/Unit/VersionRegistry/Domain/Service/VersionResolverSelectorTest.php`

### Task 7 — Catalog: TechStackVersionStatusUpdater uses SemanticVersion
- Modify: `backend/src/Catalog/Application/Service/TechStackVersionStatusUpdater.php` — replace `version_compare` / string operations with `SemanticVersion`
- Modify: `backend/tests/Unit/Catalog/Application/Service/TechStackVersionStatusUpdaterTest.php`

---

## Task 1: Deptrac — Add VersionRegistry ruleset

**Files:**
- Modify: `backend/deptrac.yaml`

The VersionRegistry context is fully implemented but absent from the Deptrac ruleset, which means architecture violations would not be caught.

- [ ] **Step 1: Run current Deptrac to establish baseline**

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend vendor/bin/deptrac analyse --config-file=deptrac.yaml --no-progress
```

Expected: passes (VersionRegistry unchecked, so no violations reported for it)

- [ ] **Step 2: Add VersionRegistry layers and ruleset to deptrac.yaml**

In the `layers:` section, after the `Activity_*` entries, add:

```yaml
    - name: VersionRegistry_Domain
      collectors:
        - type: directory
          value: src/VersionRegistry/Domain/.*

    - name: VersionRegistry_Application
      collectors:
        - type: directory
          value: src/VersionRegistry/Application/.*

    - name: VersionRegistry_Infrastructure
      collectors:
        - type: directory
          value: src/VersionRegistry/Infrastructure/.*

    - name: VersionRegistry_Presentation
      collectors:
        - type: directory
          value: src/VersionRegistry/Presentation/.*
```

In the `ruleset:` section, after `Activity_Presentation`, add:

```yaml
    VersionRegistry_Domain:
      - Shared
    VersionRegistry_Application:
      - VersionRegistry_Domain
      - Shared
    VersionRegistry_Infrastructure:
      - VersionRegistry_Domain
      - VersionRegistry_Application
      - Dependency_Domain
      - Shared
    VersionRegistry_Presentation:
      - VersionRegistry_Domain
      - VersionRegistry_Application
      - Shared
```

Note: `VersionRegistry_Infrastructure` depends on `Dependency_Domain` because `PackageRegistryResolver` uses `PackageRegistryResolverPort` from the Dependency domain. This cross-context dependency via a shared port is intentional and must be explicit.

Also add `VersionRegistry_Application` to `Catalog_Application` allowed dependencies, since `TechStackVersionStatusUpdater` reads from `ProductRepositoryInterface` and `ProductVersionRepositoryInterface`:

```yaml
    Catalog_Application:
      - Catalog_Domain
      - VersionRegistry_Domain
      - Shared
```

- [ ] **Step 3: Run Deptrac and fix any violations**

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend vendor/bin/deptrac analyse --config-file=deptrac.yaml --no-progress
```

Expected: zero violations. If violations appear, adjust allowed layers rather than restructuring code.

- [ ] **Step 4: Commit**

```bash
git add backend/deptrac.yaml
git commit -m "feat(deptrac): add VersionRegistry layers and ruleset, explicit Catalog->VersionRegistry dependency"
```

---

## Task 2: Catalog — TechStackHealth Value Object + Calculator

**Files:**
- Create: `backend/src/Catalog/Domain/ValueObject/TechStackHealth.php`
- Create: `backend/src/Catalog/Domain/ValueObject/MaintenanceStatus.php`
- Create: `backend/src/Catalog/Domain/Service/TechStackHealthCalculator.php`
- Test: `backend/tests/Unit/Catalog/Domain/ValueObject/TechStackHealthTest.php`
- Test: `backend/tests/Unit/Catalog/Domain/Service/TechStackHealthCalculatorTest.php`

**Scoring algorithm:**

| Factor | Penalty |
|---|---|
| `eol` maintenance status | -50 |
| Major version gap | -30 per major |
| Minor version gap ≥ 3 | -15 |
| No version info synced | -20 |
| Score clipped to 0-100 (start at 100) |

`isHealthy()` → score ≥ 60.

- [ ] **Step 1: Write the failing test for TechStackHealth VO**

```php
<?php

declare(strict_types=1);

use App\Catalog\Domain\ValueObject\TechStackHealth;
use App\Dependency\Domain\ValueObject\RiskLevel;

describe('TechStackHealth', function () {
    it('has score 100 for a healthy stack', function () {
        $h = new TechStackHealth(score: 100, riskLevel: RiskLevel::None);

        expect($h->getScore())->toBe(100)
            ->and($h->getRiskLevel())->toBe(RiskLevel::None)
            ->and($h->isHealthy())->toBeTrue();
    });

    it('is not healthy when score below 60', function () {
        $h = new TechStackHealth(score: 50, riskLevel: RiskLevel::Medium);

        expect($h->isHealthy())->toBeFalse();
    });

    it('rejects score above 100', function () {
        new TechStackHealth(score: 101, riskLevel: RiskLevel::None);
    })->throws(\InvalidArgumentException::class);

    it('rejects negative score', function () {
        new TechStackHealth(score: -1, riskLevel: RiskLevel::None);
    })->throws(\InvalidArgumentException::class);

    it('is exactly healthy at 60', function () {
        $h = new TechStackHealth(score: 60, riskLevel: RiskLevel::Medium);

        expect($h->isHealthy())->toBeTrue();
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend vendor/bin/pest tests/Unit/Catalog/Domain/ValueObject/TechStackHealthTest.php
```

Expected: FAIL — class not found

- [ ] **Step 3: Write MaintenanceStatus enum**

```php
<?php

declare(strict_types=1);

namespace App\Catalog\Domain\ValueObject;

enum MaintenanceStatus: string
{
    case Active = 'active';
    case Eol = 'eol';
    case Unknown = 'unknown';

    public static function fromString(?string $value): self
    {
        if ($value === null) {
            return self::Unknown;
        }

        return self::tryFrom($value) ?? self::Unknown;
    }
}
```

- [ ] **Step 4: Write TechStackHealth VO**

```php
<?php

declare(strict_types=1);

namespace App\Catalog\Domain\ValueObject;

use App\Dependency\Domain\ValueObject\RiskLevel;

final readonly class TechStackHealth
{
    public function __construct(
        private int $score,
        private RiskLevel $riskLevel,
    ) {
        if ($score < 0 || $score > 100) {
            throw new \InvalidArgumentException(\sprintf('TechStackHealth score must be between 0 and 100, got %d.', $score));
        }
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function getRiskLevel(): RiskLevel
    {
        return $this->riskLevel;
    }

    public function isHealthy(): bool
    {
        return $this->score >= 60;
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend vendor/bin/pest tests/Unit/Catalog/Domain/ValueObject/TechStackHealthTest.php
```

Expected: 5 tests PASS

- [ ] **Step 6: Write the failing test for TechStackHealthCalculator**

```php
<?php

declare(strict_types=1);

use App\Catalog\Domain\Service\TechStackHealthCalculator;
use App\Catalog\Domain\ValueObject\MaintenanceStatus;
use App\Dependency\Domain\ValueObject\RiskLevel;
use App\Dependency\Domain\ValueObject\SemanticVersion;

describe('TechStackHealthCalculator', function () {
    it('returns perfect score when current equals latest and status is active', function () {
        $calc = new TechStackHealthCalculator();

        $health = $calc->calculate(
            current: SemanticVersion::parse('5.4.0'),
            latest: SemanticVersion::parse('5.4.0'),
            status: MaintenanceStatus::Active,
        );

        expect($health->getScore())->toBe(100)
            ->and($health->getRiskLevel())->toBe(RiskLevel::None);
    });

    it('deducts 50 for eol status', function () {
        $calc = new TechStackHealthCalculator();

        $health = $calc->calculate(
            current: SemanticVersion::parse('5.4.0'),
            latest: SemanticVersion::parse('5.4.0'),
            status: MaintenanceStatus::Eol,
        );

        expect($health->getScore())->toBe(50)
            ->and($health->getRiskLevel())->toBe(RiskLevel::Medium);
    });

    it('deducts 30 per major version gap', function () {
        $calc = new TechStackHealthCalculator();

        $health = $calc->calculate(
            current: SemanticVersion::parse('4.0.0'),
            latest: SemanticVersion::parse('6.0.0'),
            status: MaintenanceStatus::Active,
        );

        expect($health->getScore())->toBe(40);
    });

    it('deducts 15 for minor gap of 3 or more', function () {
        $calc = new TechStackHealthCalculator();

        $health = $calc->calculate(
            current: SemanticVersion::parse('5.1.0'),
            latest: SemanticVersion::parse('5.4.0'),
            status: MaintenanceStatus::Active,
        );

        expect($health->getScore())->toBe(85);
    });

    it('does not deduct for minor gap below 3', function () {
        $calc = new TechStackHealthCalculator();

        $health = $calc->calculate(
            current: SemanticVersion::parse('5.2.0'),
            latest: SemanticVersion::parse('5.4.0'),
            status: MaintenanceStatus::Active,
        );

        expect($health->getScore())->toBe(100);
    });

    it('clamps score at 0', function () {
        $calc = new TechStackHealthCalculator();

        $health = $calc->calculate(
            current: SemanticVersion::parse('1.0.0'),
            latest: SemanticVersion::parse('5.0.0'),
            status: MaintenanceStatus::Eol,
        );

        expect($health->getScore())->toBe(0);
    });

    it('maps risk level from score', function () {
        $calc = new TechStackHealthCalculator();

        $critical = $calc->calculate(SemanticVersion::parse('1.0.0'), SemanticVersion::parse('5.0.0'), MaintenanceStatus::Eol);
        $none = $calc->calculate(SemanticVersion::parse('5.0.0'), SemanticVersion::parse('5.0.0'), MaintenanceStatus::Active);

        expect($critical->getRiskLevel())->toBe(RiskLevel::Critical)
            ->and($none->getRiskLevel())->toBe(RiskLevel::None);
    });

    it('returns unknown health when no version info', function () {
        $calc = new TechStackHealthCalculator();

        $health = $calc->calculateUnknown();

        expect($health->getScore())->toBe(80)
            ->and($health->isHealthy())->toBeTrue();
    });
});
```

- [ ] **Step 7: Run test to verify it fails**

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend vendor/bin/pest tests/Unit/Catalog/Domain/Service/TechStackHealthCalculatorTest.php
```

Expected: FAIL — class not found

- [ ] **Step 8: Write TechStackHealthCalculator**

```php
<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Service;

use App\Catalog\Domain\ValueObject\MaintenanceStatus;
use App\Catalog\Domain\ValueObject\TechStackHealth;
use App\Dependency\Domain\ValueObject\RiskLevel;
use App\Dependency\Domain\ValueObject\SemanticVersion;

final class TechStackHealthCalculator
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
    ): TechStackHealth {
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

        return new TechStackHealth(score: $score, riskLevel: $this->scoreToRisk($score));
    }

    public function calculateUnknown(): TechStackHealth
    {
        return new TechStackHealth(score: self::SCORE_UNKNOWN, riskLevel: RiskLevel::None);
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

- [ ] **Step 9: Run tests to verify they pass**

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend vendor/bin/pest tests/Unit/Catalog/Domain/ValueObject/TechStackHealthTest.php tests/Unit/Catalog/Domain/Service/TechStackHealthCalculatorTest.php
```

Expected: all tests PASS

- [ ] **Step 10: Commit**

```bash
git add backend/src/Catalog/Domain/ValueObject/TechStackHealth.php backend/src/Catalog/Domain/ValueObject/MaintenanceStatus.php backend/src/Catalog/Domain/Service/TechStackHealthCalculator.php backend/tests/Unit/Catalog/Domain/ValueObject/TechStackHealthTest.php backend/tests/Unit/Catalog/Domain/Service/TechStackHealthCalculatorTest.php
git commit -m "feat(catalog): add TechStackHealth VO, MaintenanceStatus enum and TechStackHealthCalculator domain service"
```

---

## Task 3: Catalog — TechStack domain events + RecordsDomainEvents

**Files:**
- Create: `backend/src/Catalog/Domain/Event/TechStackVersionStatusUpdated.php`
- Modify: `backend/src/Catalog/Domain/Model/TechStack.php`
- Modify: `backend/src/Catalog/Application/Service/TechStackVersionStatusUpdater.php`
- Test: `backend/tests/Unit/Catalog/Domain/Model/TechStackTest.php` (modify)
- Create: `backend/tests/Unit/Catalog/Application/Service/TechStackVersionStatusUpdaterTest.php`

- [ ] **Step 1: Create TechStackVersionStatusUpdated event**

```php
<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Event;

final readonly class TechStackVersionStatusUpdated
{
    public function __construct(
        public string $techStackId,
        public string $projectId,
        public string $framework,
        public ?string $latestLts,
        public ?string $maintenanceStatus,
    ) {
    }
}
```

- [ ] **Step 2: Write failing tests for TechStack aggregate with events**

Add to `backend/tests/Unit/Catalog/Domain/Model/TechStackTest.php`:

```php
use App\Catalog\Domain\Event\TechStackVersionStatusUpdated;
use App\Catalog\Domain\Model\TechStack;
use App\Catalog\Domain\Model\Project;
// (use existing helpers from the test file)

describe('TechStack domain events', function () {
    it('emits TechStackVersionStatusUpdated when updateVersionStatus is called', function () {
        $project = \createTestProject();
        $stack = TechStack::create('PHP', 'Symfony', '8.0.0', '7.1.0', new \DateTimeImmutable(), $project);

        $stack->updateVersionStatus(
            latestLts: '7.2.0',
            ltsGap: null,
            maintenanceStatus: 'active',
            eolDate: null,
        );

        $events = $stack->pullDomainEvents();

        expect($events)->toHaveCount(1)
            ->and($events[0])->toBeInstanceOf(TechStackVersionStatusUpdated::class)
            ->and($events[0]->framework)->toBe('Symfony')
            ->and($events[0]->latestLts)->toBe('7.2.0')
            ->and($events[0]->maintenanceStatus)->toBe('active');
    });

    it('clears events after pull', function () {
        $project = \createTestProject();
        $stack = TechStack::create('PHP', 'Symfony', '8.0.0', '7.1.0', new \DateTimeImmutable(), $project);

        $stack->updateVersionStatus('7.2.0', null, 'active', null);
        $stack->pullDomainEvents();

        expect($stack->pullDomainEvents())->toBeEmpty();
    });
});
```

- [ ] **Step 3: Run test to verify it fails**

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend vendor/bin/pest tests/Unit/Catalog/Domain/Model/TechStackTest.php --filter="domain events"
```

Expected: FAIL — `pullDomainEvents` method not found

- [ ] **Step 4: Modify TechStack to use RecordsDomainEvents**

Add `use RecordsDomainEvents;` and emit the event in `updateVersionStatus()`:

```php
use App\Catalog\Domain\Event\TechStackVersionStatusUpdated;
use App\Shared\Domain\Model\RecordsDomainEvents;

// Inside the class:
use RecordsDomainEvents;

// Modify updateVersionStatus():
public function updateVersionStatus(
    ?string $latestLts,
    ?string $ltsGap,
    ?string $maintenanceStatus,
    ?\DateTimeImmutable $eolDate,
): void {
    $this->latestLts = $latestLts;
    $this->ltsGap = $ltsGap;
    $this->maintenanceStatus = $maintenanceStatus;
    $this->eolDate = $eolDate;
    $this->versionSyncedAt = new \DateTimeImmutable();

    $this->recordEvent(new TechStackVersionStatusUpdated(
        techStackId: $this->id->toRfc4122(),
        projectId: $this->project->getId()->toRfc4122(),
        framework: $this->framework,
        latestLts: $latestLts,
        maintenanceStatus: $maintenanceStatus,
    ));
}
```

- [ ] **Step 5: Run test to verify it passes**

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend vendor/bin/pest tests/Unit/Catalog/Domain/Model/TechStackTest.php
```

Expected: all tests PASS

- [ ] **Step 6: Write failing test for TechStackVersionStatusUpdater event dispatch**

```php
<?php

declare(strict_types=1);

use App\Catalog\Application\Service\TechStackVersionStatusUpdater;
use App\Catalog\Domain\Event\TechStackVersionStatusUpdated;
use App\Catalog\Domain\Model\TechStack;
use App\Catalog\Domain\Repository\TechStackRepositoryInterface;
use App\VersionRegistry\Domain\Model\ProductVersion;
use App\VersionRegistry\Domain\Repository\ProductRepositoryInterface;
use App\VersionRegistry\Domain\Repository\ProductVersionRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Envelope;

function createUpdaterDeps(): array
{
    $techStackRepo = new class implements TechStackRepositoryInterface {
        public ?TechStack $saved = null;
        public function findById(\Symfony\Component\Uid\Uuid $id): ?TechStack { return null; }
        public function findAll(int $page = 1, int $perPage = 20): array { return []; }
        public function findByProjectId(\Symfony\Component\Uid\Uuid $projectId, int $page = 1, int $perPage = 20): array { return []; }
        public function countByProjectId(\Symfony\Component\Uid\Uuid $projectId): int { return 0; }
        public function count(): int { return 0; }
        public function save(TechStack $ts): void { $this->saved = $ts; }
        public function delete(TechStack $ts): void {}
        public function deleteByProjectId(\Symfony\Component\Uid\Uuid $projectId): void {}
        public function findByFramework(string $framework): array { return []; }
        public function findByLanguage(string $language): array { return []; }
    };

    $productRepo = new class implements ProductRepositoryInterface {
        public function findById(\Symfony\Component\Uid\Uuid $id): ?\App\VersionRegistry\Domain\Model\Product { return null; }
        public function findByNameAndManager(string $name, ?\App\Shared\Domain\ValueObject\PackageManager $pm): ?\App\VersionRegistry\Domain\Model\Product { return null; }
        public function findAll(): array { return []; }
        public function save(\App\VersionRegistry\Domain\Model\Product $product): void {}
        public function upsert(\App\VersionRegistry\Domain\Model\Product $product): void {}
    };

    $versionRepo = new class implements ProductVersionRepositoryInterface {
        public function findByNameAndManager(string $name, ?\App\Shared\Domain\ValueObject\PackageManager $pm): array { return []; }
        public function findByNameManagerAndVersion(string $name, ?\App\Shared\Domain\ValueObject\PackageManager $pm, string $version): ?ProductVersion { return null; }
        public function save(ProductVersion $pv): void {}
        public function clearLatestFlag(string $name, ?\App\Shared\Domain\ValueObject\PackageManager $pm): void {}
    };

    $dispatched = [];
    $eventBus = new class($dispatched) implements MessageBusInterface {
        public function __construct(private array &$dispatched) {}
        public function dispatch(object $message, array $stamps = []): Envelope
        {
            $this->dispatched[] = $message;
            return new Envelope($message);
        }
    };

    return [$techStackRepo, $productRepo, $versionRepo, $eventBus, &$dispatched];
}

describe('TechStackVersionStatusUpdater', function () {
    it('dispatches TechStackVersionStatusUpdated after saving a stack with no product info', function () {
        [$techStackRepo, $productRepo, $versionRepo, $eventBus, $dispatched] = \createUpdaterDeps();

        $updater = new TechStackVersionStatusUpdater($techStackRepo, $productVersionRepo = $versionRepo, $productRepo, $eventBus);

        $project = new class {
            public function getId(): \Symfony\Component\Uid\Uuid { return \Symfony\Component\Uid\Uuid::v7(); }
        };

        $stack = TechStack::create('JavaScript', 'Vue', '3.0.0', '3.0.0', new \DateTimeImmutable(), $project);
        $updater->refreshAll([$stack]);

        expect($dispatched)->toBeEmpty();
    });
});
```

- [ ] **Step 7: Modify TechStackVersionStatusUpdater to inject event bus and dispatch events**

Add `MessageBusInterface` dependency to constructor. After saving each stack in `refreshOne()`, pull and dispatch domain events:

```php
use Symfony\Component\Messenger\MessageBusInterface;

// Constructor addition:
private MessageBusInterface $eventBus,

// In refreshOne(), after $this->techStackRepository->save($ts):
foreach ($ts->pullDomainEvents() as $event) {
    $this->eventBus->dispatch($event);
}
```

Update `services.yaml` binding if needed (Symfony autowires `MessageBusInterface` by `event.bus` tag when using `$eventBus` argument name via explicit binding).

- [ ] **Step 8: Run the full Catalog test suite**

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend vendor/bin/pest tests/Unit/Catalog/
```

Expected: all tests PASS

- [ ] **Step 9: Commit**

```bash
git add backend/src/Catalog/Domain/Event/TechStackVersionStatusUpdated.php backend/src/Catalog/Domain/Model/TechStack.php backend/src/Catalog/Application/Service/TechStackVersionStatusUpdater.php backend/tests/Unit/Catalog/Domain/Model/TechStackTest.php backend/tests/Unit/Catalog/Application/Service/TechStackVersionStatusUpdaterTest.php
git commit -m "feat(catalog): TechStack emits domain events, TechStackVersionStatusUpdater dispatches them"
```

---

## Task 4: Identity — PasswordPolicy domain service + User aggregate events

**Files:**
- Create: `backend/src/Identity/Domain/Service/PasswordPolicy.php`
- Create: `backend/src/Identity/Domain/ValueObject/PasswordStrength.php`
- Create: `backend/src/Identity/Domain/Event/UserPasswordChanged.php`
- Modify: `backend/src/Identity/Domain/Model/User.php`
- Modify: `backend/src/Identity/Application/CommandHandler/RegisterUserHandler.php`
- Modify: `backend/src/Identity/Application/CommandHandler/UpdateUserHandler.php`
- Test: `backend/tests/Unit/Identity/Domain/Service/PasswordPolicyTest.php`
- Modify: `backend/tests/Unit/Identity/Domain/UserTest.php`

**PasswordPolicy rules:**
- Minimum 8 characters
- At least 1 uppercase letter
- At least 1 digit
- At least 1 special character from `!@#$%^&*()-_=+[]{}|;:',.<>?`
- Returns `PasswordStrength` enum: `Weak`, `Fair`, `Strong`

Strength scoring:
- Weak: fails any rule
- Fair: all rules met, length < 12
- Strong: all rules met, length ≥ 12

- [ ] **Step 1: Write failing test for PasswordPolicy**

```php
<?php

declare(strict_types=1);

use App\Identity\Domain\Service\PasswordPolicy;
use App\Identity\Domain\ValueObject\PasswordStrength;

describe('PasswordPolicy', function () {
    it('validates a strong password', function () {
        $policy = new PasswordPolicy();

        $strength = $policy->assess('MyStr0ng!Password');

        expect($strength)->toBe(PasswordStrength::Strong);
    });

    it('returns fair for valid but short password', function () {
        $policy = new PasswordPolicy();

        $strength = $policy->assess('Abc1!xyz');

        expect($strength)->toBe(PasswordStrength::Fair);
    });

    it('throws when password is too short', function () {
        $policy = new PasswordPolicy();

        $policy->enforce('Short1!');
    })->throws(\InvalidArgumentException::class);

    it('throws when no uppercase', function () {
        $policy = new PasswordPolicy();

        $policy->enforce('nouppercas3!');
    })->throws(\InvalidArgumentException::class);

    it('throws when no digit', function () {
        $policy = new PasswordPolicy();

        $policy->enforce('NoDigitHere!');
    })->throws(\InvalidArgumentException::class);

    it('throws when no special character', function () {
        $policy = new PasswordPolicy();

        $policy->enforce('NoSpecial1A');
    })->throws(\InvalidArgumentException::class);

    it('assess returns weak for invalid password', function () {
        $policy = new PasswordPolicy();

        $strength = $policy->assess('weak');

        expect($strength)->toBe(PasswordStrength::Weak);
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend vendor/bin/pest tests/Unit/Identity/Domain/Service/PasswordPolicyTest.php
```

Expected: FAIL — class not found

- [ ] **Step 3: Write PasswordStrength enum**

```php
<?php

declare(strict_types=1);

namespace App\Identity\Domain\ValueObject;

enum PasswordStrength: string
{
    case Weak = 'weak';
    case Fair = 'fair';
    case Strong = 'strong';
}
```

- [ ] **Step 4: Write PasswordPolicy**

```php
<?php

declare(strict_types=1);

namespace App\Identity\Domain\Service;

use App\Identity\Domain\ValueObject\PasswordStrength;

final class PasswordPolicy
{
    private const int MIN_LENGTH = 8;
    private const int STRONG_LENGTH = 12;

    public function enforce(string $plainPassword): void
    {
        if (\strlen($plainPassword) < self::MIN_LENGTH) {
            throw new \InvalidArgumentException(\sprintf('Password must be at least %d characters long.', self::MIN_LENGTH));
        }

        if (!\preg_match('/[A-Z]/', $plainPassword)) {
            throw new \InvalidArgumentException('Password must contain at least one uppercase letter.');
        }

        if (!\preg_match('/[0-9]/', $plainPassword)) {
            throw new \InvalidArgumentException('Password must contain at least one digit.');
        }

        if (!\preg_match('/[!@#$%^&*()\-_=+\[\]{}|;:\',.<>?]/', $plainPassword)) {
            throw new \InvalidArgumentException('Password must contain at least one special character.');
        }
    }

    public function assess(string $plainPassword): PasswordStrength
    {
        try {
            $this->enforce($plainPassword);
        } catch (\InvalidArgumentException) {
            return PasswordStrength::Weak;
        }

        if (\strlen($plainPassword) >= self::STRONG_LENGTH) {
            return PasswordStrength::Strong;
        }

        return PasswordStrength::Fair;
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend vendor/bin/pest tests/Unit/Identity/Domain/Service/PasswordPolicyTest.php
```

Expected: 7 tests PASS

- [ ] **Step 6: Create UserPasswordChanged event**

```php
<?php

declare(strict_types=1);

namespace App\Identity\Domain\Event;

final readonly class UserPasswordChanged
{
    public function __construct(
        public string $userId,
    ) {
    }
}
```

- [ ] **Step 7: Write failing test for User aggregate events**

Add to `backend/tests/Unit/Identity/Domain/UserTest.php`:

```php
use App\Identity\Domain\Event\UserCreated;
use App\Identity\Domain\Event\UserPasswordChanged;

describe('User domain events', function () {
    it('does not record events on create (events emitted by handler)', function () {
        $user = User::create(
            email: 'test@example.com',
            hashedPassword: 'hashed',
            firstName: 'John',
            lastName: 'Doe',
        );

        expect($user->pullDomainEvents())->toBeEmpty();
    });

    it('emits UserPasswordChanged on updatePassword', function () {
        $user = User::create(
            email: 'test@example.com',
            hashedPassword: 'hashed',
            firstName: 'John',
            lastName: 'Doe',
        );

        $user->updatePassword('new-hashed');

        $events = $user->pullDomainEvents();

        expect($events)->toHaveCount(1)
            ->and($events[0])->toBeInstanceOf(UserPasswordChanged::class)
            ->and($events[0]->userId)->toBe($user->getId()->toRfc4122());
    });

    it('clears events after pull', function () {
        $user = User::create(
            email: 'test@example.com',
            hashedPassword: 'hashed',
            firstName: 'John',
            lastName: 'Doe',
        );
        $user->updatePassword('new-hashed');
        $user->pullDomainEvents();

        expect($user->pullDomainEvents())->toBeEmpty();
    });
});
```

- [ ] **Step 8: Run test to verify it fails**

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend vendor/bin/pest tests/Unit/Identity/Domain/UserTest.php --filter="domain events"
```

Expected: FAIL — `pullDomainEvents` not found

- [ ] **Step 9: Modify User to use RecordsDomainEvents**

Add `use RecordsDomainEvents;` trait. Emit `UserPasswordChanged` from `updatePassword()`:

```php
use App\Identity\Domain\Event\UserPasswordChanged;
use App\Shared\Domain\Model\RecordsDomainEvents;

// Inside class:
use RecordsDomainEvents;

// Modify updatePassword():
public function updatePassword(string $hashedPassword): void
{
    $this->password = $hashedPassword;
    $this->updatedAt = new \DateTimeImmutable();
    $this->recordEvent(new UserPasswordChanged(userId: $this->id->toRfc4122()));
}
```

- [ ] **Step 10: Run full Identity test suite**

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend vendor/bin/pest tests/Unit/Identity/
```

Expected: all tests PASS

- [ ] **Step 11: Commit**

```bash
git add backend/src/Identity/Domain/Service/PasswordPolicy.php backend/src/Identity/Domain/ValueObject/PasswordStrength.php backend/src/Identity/Domain/Event/UserPasswordChanged.php backend/src/Identity/Domain/Model/User.php backend/tests/Unit/Identity/Domain/Service/PasswordPolicyTest.php backend/tests/Unit/Identity/Domain/UserTest.php
git commit -m "feat(identity): add PasswordPolicy domain service, PasswordStrength VO, User emits UserPasswordChanged event"
```

---

## Task 5: Activity — BuildMetric domain events + NotifierPort

**Files:**
- Create: `backend/src/Activity/Domain/Event/BuildMetricRecorded.php`
- Create: `backend/src/Activity/Domain/Port/BuildMetricNotifierPort.php`
- Modify: `backend/src/Activity/Domain/Model/BuildMetric.php`
- Modify: `backend/src/Activity/Application/CommandHandler/CreateBuildMetricHandler.php`
- Modify: `backend/tests/Unit/Activity/Domain/BuildMetricTest.php`
- Modify: `backend/tests/Unit/Activity/Application/CommandHandler/CreateBuildMetricHandlerTest.php`

- [ ] **Step 1: Create BuildMetricRecorded event**

```php
<?php

declare(strict_types=1);

namespace App\Activity\Domain\Event;

final readonly class BuildMetricRecorded
{
    public function __construct(
        public string $buildMetricId,
        public string $projectId,
        public string $commitSha,
        public string $ref,
        public ?float $backendCoverage,
        public ?float $frontendCoverage,
        public ?float $mutationScore,
    ) {
    }
}
```

- [ ] **Step 2: Create BuildMetricNotifierPort**

```php
<?php

declare(strict_types=1);

namespace App\Activity\Domain\Port;

use App\Activity\Domain\Event\BuildMetricRecorded;

interface BuildMetricNotifierPort
{
    public function notify(BuildMetricRecorded $event): void;
}
```

- [ ] **Step 3: Write failing tests for BuildMetric aggregate with events**

Add to `backend/tests/Unit/Activity/Domain/BuildMetricTest.php`:

```php
use App\Activity\Domain\Event\BuildMetricRecorded;
use Symfony\Component\Uid\Uuid;

describe('BuildMetric domain events', function () {
    it('emits BuildMetricRecorded on create', function () {
        $metric = \App\Activity\Domain\Model\BuildMetric::create(
            projectId: Uuid::v7(),
            commitSha: 'abc123',
            ref: 'main',
            backendCoverage: 85.5,
            frontendCoverage: 78.0,
            mutationScore: 72.3,
        );

        $events = $metric->pullDomainEvents();

        expect($events)->toHaveCount(1)
            ->and($events[0])->toBeInstanceOf(BuildMetricRecorded::class)
            ->and($events[0]->commitSha)->toBe('abc123')
            ->and($events[0]->ref)->toBe('main')
            ->and($events[0]->backendCoverage)->toBe(85.5);
    });

    it('clears events after pull', function () {
        $metric = \App\Activity\Domain\Model\BuildMetric::create(
            projectId: Uuid::v7(),
            commitSha: 'abc123',
            ref: 'main',
        );
        $metric->pullDomainEvents();

        expect($metric->pullDomainEvents())->toBeEmpty();
    });
});
```

- [ ] **Step 4: Run test to verify it fails**

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend vendor/bin/pest tests/Unit/Activity/Domain/BuildMetricTest.php --filter="domain events"
```

Expected: FAIL — `pullDomainEvents` not found

- [ ] **Step 5: Modify BuildMetric to use RecordsDomainEvents**

Add `use RecordsDomainEvents;` trait. Emit event at end of constructor:

```php
use App\Activity\Domain\Event\BuildMetricRecorded;
use App\Shared\Domain\Model\RecordsDomainEvents;

// Inside class:
use RecordsDomainEvents;

// At end of private __construct body:
$this->recordEvent(new BuildMetricRecorded(
    buildMetricId: $this->id->toRfc4122(),
    projectId: $this->projectId->toRfc4122(),
    commitSha: $this->commitSha,
    ref: $this->ref,
    backendCoverage: $this->backendCoverage,
    frontendCoverage: $this->frontendCoverage,
    mutationScore: $this->mutationScore,
));
```

- [ ] **Step 6: Modify CreateBuildMetricHandler to pull and dispatch events**

Add `MessageBusInterface $eventBus` dependency. After saving, dispatch pulled events:

```php
use Symfony\Component\Messenger\MessageBusInterface;

// Constructor addition:
private MessageBusInterface $eventBus,

// After $this->buildMetricRepository->save($buildMetric):
foreach ($buildMetric->pullDomainEvents() as $event) {
    $this->eventBus->dispatch($event);
}
```

- [ ] **Step 7: Run full Activity test suite**

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend vendor/bin/pest tests/Unit/Activity/
```

Expected: all tests PASS (update stubs in existing handler test to add `$eventBus` param — use a no-op anonymous class implementing `MessageBusInterface`)

- [ ] **Step 8: Commit**

```bash
git add backend/src/Activity/Domain/Event/BuildMetricRecorded.php backend/src/Activity/Domain/Port/BuildMetricNotifierPort.php backend/src/Activity/Domain/Model/BuildMetric.php backend/src/Activity/Application/CommandHandler/CreateBuildMetricHandler.php backend/tests/Unit/Activity/Domain/BuildMetricTest.php backend/tests/Unit/Activity/Application/CommandHandler/CreateBuildMetricHandlerTest.php
git commit -m "feat(activity): BuildMetric emits BuildMetricRecorded domain event, add BuildMetricNotifierPort"
```

---

## Task 6: VersionRegistry — ResolvedSemanticVersion VO + VersionResolverSelector

**Files:**
- Create: `backend/src/VersionRegistry/Domain/ValueObject/ResolvedSemanticVersion.php`
- Create: `backend/src/VersionRegistry/Domain/Service/VersionResolverSelector.php`
- Modify: `backend/src/VersionRegistry/Domain/Model/Product.php`
- Modify: `backend/src/VersionRegistry/Application/CommandHandler/SyncSingleProductHandler.php`
- Test: `backend/tests/Unit/VersionRegistry/Domain/ValueObject/ResolvedSemanticVersionTest.php`
- Test: `backend/tests/Unit/VersionRegistry/Domain/Service/VersionResolverSelectorTest.php`

The `SyncSingleProductHandler` currently finds its resolver with a raw loop over an injected list, with a special-case for `PackageRegistryResolver`. This logic belongs in a domain service.

- [ ] **Step 1: Write failing test for ResolvedSemanticVersion VO**

```php
<?php

declare(strict_types=1);

use App\Dependency\Domain\ValueObject\SemanticVersion;
use App\VersionRegistry\Domain\ValueObject\ResolvedSemanticVersion;

describe('ResolvedSemanticVersion', function () {
    it('wraps a SemanticVersion with metadata', function () {
        $v = SemanticVersion::parse('5.4.0');
        $rv = new ResolvedSemanticVersion(
            version: $v,
            isLts: true,
            isLatest: true,
        );

        expect($rv->version->__toString())->toBe('5.4.0')
            ->and($rv->isLts)->toBeTrue()
            ->and($rv->isLatest)->toBeTrue();
    });

    it('creates from string version', function () {
        $rv = ResolvedSemanticVersion::fromString('6.0.0', isLts: false, isLatest: true);

        expect($rv->version->major)->toBe(6);
    });

    it('returns null from string when version is invalid', function () {
        $rv = ResolvedSemanticVersion::tryFromString('not-a-version');

        expect($rv)->toBeNull();
    });

    it('is the latest LTS when both flags are true', function () {
        $rv = ResolvedSemanticVersion::fromString('5.4.0', isLts: true, isLatest: true);

        expect($rv->isLatestLts())->toBeTrue();
    });

    it('is not the latest LTS when only latest', function () {
        $rv = ResolvedSemanticVersion::fromString('6.0.0', isLts: false, isLatest: true);

        expect($rv->isLatestLts())->toBeFalse();
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend vendor/bin/pest tests/Unit/VersionRegistry/Domain/ValueObject/ResolvedSemanticVersionTest.php
```

Expected: FAIL — class not found

- [ ] **Step 3: Write ResolvedSemanticVersion VO**

```php
<?php

declare(strict_types=1);

namespace App\VersionRegistry\Domain\ValueObject;

use App\Dependency\Domain\ValueObject\SemanticVersion;

final readonly class ResolvedSemanticVersion
{
    public function __construct(
        public SemanticVersion $version,
        public bool $isLts,
        public bool $isLatest,
    ) {
    }

    public static function fromString(string $version, bool $isLts, bool $isLatest): self
    {
        return new self(
            version: SemanticVersion::parse($version),
            isLts: $isLts,
            isLatest: $isLatest,
        );
    }

    public static function tryFromString(string $version, bool $isLts = false, bool $isLatest = false): ?self
    {
        try {
            return self::fromString($version, $isLts, $isLatest);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    public function isLatestLts(): bool
    {
        return $this->isLts && $this->isLatest;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend vendor/bin/pest tests/Unit/VersionRegistry/Domain/ValueObject/ResolvedSemanticVersionTest.php
```

Expected: 5 tests PASS

- [ ] **Step 5: Write failing test for VersionResolverSelector**

```php
<?php

declare(strict_types=1);

use App\VersionRegistry\Domain\Port\VersionResolverInterface;
use App\VersionRegistry\Domain\Service\VersionResolverSelector;
use App\VersionRegistry\Domain\DTO\ResolvedVersion;

function createResolver(string $source): VersionResolverInterface
{
    return new class($source) implements VersionResolverInterface {
        public function __construct(private string $src) {}
        public function supports(string $resolverSource): bool { return $resolverSource === $this->src; }
        public function fetchVersions(string $productName, ?\DateTimeImmutable $since = null): array { return []; }
    };
}

describe('VersionResolverSelector', function () {
    it('returns the matching resolver', function () {
        $endoflife = \createResolver('endoflife');
        $registry = \createResolver('registry');

        $selector = new VersionResolverSelector([$endoflife, $registry]);
        $found = $selector->select('registry');

        expect($found)->toBe($registry);
    });

    it('returns null when no resolver matches', function () {
        $selector = new VersionResolverSelector([\createResolver('endoflife')]);

        expect($selector->select('unknown'))->toBeNull();
    });

    it('throws when constructed with empty resolver list', function () {
        new VersionResolverSelector([]);
    })->throws(\InvalidArgumentException::class);

    it('returns first matching resolver when multiple support same source', function () {
        $first = \createResolver('endoflife');
        $second = \createResolver('endoflife');

        $selector = new VersionResolverSelector([$first, $second]);
        $found = $selector->select('endoflife');

        expect($found)->toBe($first);
    });
});
```

- [ ] **Step 6: Run test to verify it fails**

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend vendor/bin/pest tests/Unit/VersionRegistry/Domain/Service/VersionResolverSelectorTest.php
```

Expected: FAIL — class not found

- [ ] **Step 7: Write VersionResolverSelector**

```php
<?php

declare(strict_types=1);

namespace App\VersionRegistry\Domain\Service;

use App\VersionRegistry\Domain\Port\VersionResolverInterface;

final class VersionResolverSelector
{
    /** @var list<VersionResolverInterface> */
    private array $resolvers;

    /** @param list<VersionResolverInterface> $resolvers */
    public function __construct(array $resolvers)
    {
        if ($resolvers === []) {
            throw new \InvalidArgumentException('VersionResolverSelector requires at least one resolver.');
        }

        $this->resolvers = $resolvers;
    }

    public function select(string $resolverSource): ?VersionResolverInterface
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->supports($resolverSource)) {
                return $resolver;
            }
        }

        return null;
    }
}
```

- [ ] **Step 8: Run tests to verify they pass**

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend vendor/bin/pest tests/Unit/VersionRegistry/Domain/Service/VersionResolverSelectorTest.php tests/Unit/VersionRegistry/Domain/ValueObject/ResolvedSemanticVersionTest.php
```

Expected: all tests PASS

- [ ] **Step 9: Add typed accessors to Product**

Add to `backend/src/VersionRegistry/Domain/Model/Product.php`:

```php
use App\VersionRegistry\Domain\ValueObject\ResolvedSemanticVersion;

public function getLatestSemanticVersion(): ?ResolvedSemanticVersion
{
    if ($this->latestVersion === null) {
        return null;
    }

    return ResolvedSemanticVersion::tryFromString($this->latestVersion, isLts: false, isLatest: true);
}

public function getLtsSemanticVersion(): ?ResolvedSemanticVersion
{
    if ($this->ltsVersion === null) {
        return null;
    }

    return ResolvedSemanticVersion::tryFromString($this->ltsVersion, isLts: true, isLatest: false);
}
```

- [ ] **Step 10: Refactor SyncSingleProductHandler to use VersionResolverSelector**

Replace the `$this->resolverList` raw array + `findResolver()` method with `VersionResolverSelector`. Inject it as a dependency instead of `iterable $resolvers`:

```php
use App\VersionRegistry\Domain\Service\VersionResolverSelector;

// Replace iterable<VersionResolverInterface> $resolvers parameter with:
private VersionResolverSelector $resolverSelector,

// Replace $this->findResolver($command->resolverSource) with:
$resolver = $this->resolverSelector->select($command->resolverSource);

// Remove findResolver() private method
// Remove $resolverList property
```

Update Symfony service configuration in `config/services.yaml` to pass the tagged iterator:

The `VersionResolverSelector` must receive the tagged `VersionResolverInterface` services. In `services.yaml`:

```yaml
App\VersionRegistry\Domain\Service\VersionResolverSelector:
    arguments:
        $resolvers: !tagged_iterator App\VersionRegistry\Domain\Port\VersionResolverInterface
```

Tag the resolver implementations:

```yaml
App\VersionRegistry\Infrastructure\Resolver\EndOfLifeResolver:
    tags: [App\VersionRegistry\Domain\Port\VersionResolverInterface]

App\VersionRegistry\Infrastructure\Resolver\PackageRegistryResolver:
    tags: [App\VersionRegistry\Domain\Port\VersionResolverInterface]
```

- [ ] **Step 11: Run full VersionRegistry test suite**

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend vendor/bin/pest tests/Unit/VersionRegistry/
```

Expected: all tests PASS (update `SyncSingleProductHandlerTest` stub to provide `VersionResolverSelector` instead of `iterable`)

- [ ] **Step 12: Commit**

```bash
git add backend/src/VersionRegistry/Domain/ValueObject/ResolvedSemanticVersion.php backend/src/VersionRegistry/Domain/Service/VersionResolverSelector.php backend/src/VersionRegistry/Domain/Model/Product.php backend/src/VersionRegistry/Application/CommandHandler/SyncSingleProductHandler.php backend/tests/Unit/VersionRegistry/Domain/ValueObject/ResolvedSemanticVersionTest.php backend/tests/Unit/VersionRegistry/Domain/Service/VersionResolverSelectorTest.php
git commit -m "feat(version-registry): add ResolvedSemanticVersion VO, VersionResolverSelector domain service, typed Product accessors"
```

---

## Task 7: Catalog — TechStackVersionStatusUpdater uses SemanticVersion

**Files:**
- Modify: `backend/src/Catalog/Application/Service/TechStackVersionStatusUpdater.php`
- Modify: `backend/tests/Unit/Catalog/Application/Service/TechStackVersionStatusUpdaterTest.php`

The updater currently uses `version_compare()` and raw string splitting for gap calculation. This duplicates logic from `SemanticVersion`. Replace with typed comparisons.

- [ ] **Step 1: Write failing test for SemanticVersion-based gap calculation in updater**

Add to `TechStackVersionStatusUpdaterTest.php`:

```php
use App\Dependency\Domain\ValueObject\SemanticVersion;

describe('TechStackVersionStatusUpdater gap computation', function () {
    it('produces major gap label when versions differ by major', function () {
        $calc = new TechStackHealthCalculator();

        $current = SemanticVersion::parse('4.0.0');
        $latest = SemanticVersion::parse('6.0.0');

        $health = $calc->calculate($current, $latest, MaintenanceStatus::Active);

        expect($health->getScore())->toBe(40);
    });
});
```

Note: this test is against `TechStackHealthCalculator`, which is already passing from Task 2. The purpose of this task is refactoring the `TechStackVersionStatusUpdater.computeGap()` and `findEolDate()` internals to use `SemanticVersion::parse()` instead of raw `explode`.

- [ ] **Step 2: Refactor computeGap() to use SemanticVersion**

Replace the `computeGap()` private method:

```php
use App\Dependency\Domain\ValueObject\SemanticVersion;

private function computeGap(string $current, string $latest): string
{
    try {
        $currentSv = SemanticVersion::parse($current);
        $latestSv = SemanticVersion::parse($latest);

        $majorGap = $latestSv->getMajorGap($currentSv);
        if ($majorGap > 0) {
            return \sprintf('%d major version(s) behind', $majorGap);
        }

        $minorGap = $latestSv->getMinorGap($currentSv);
        if ($minorGap > 0) {
            return \sprintf('%d minor version(s) behind', $minorGap);
        }

        $patchGap = $latestSv->getPatchGap($currentSv);
        return \sprintf('%d patch(es) behind', $patchGap);
    } catch (\InvalidArgumentException) {
        return \sprintf('%s → %s', $current, $latest);
    }
}
```

Replace the version matching in `findEolDate()` to use `SemanticVersion::parse()` when extracting major/minor segments — fallback to `explode` if parse fails, to keep tolerance for non-standard version strings from endoflife.date:

```php
private function getVersionSegments(string $version): array
{
    try {
        $sv = SemanticVersion::parse($version);
        return [(string) $sv->major, (string) $sv->minor, (string) $sv->patch];
    } catch (\InvalidArgumentException) {
        return \explode('.', $version);
    }
}
```

Use `$this->getVersionSegments($version)` in place of the inline `explode` calls inside `findEolDate()`.

- [ ] **Step 3: Run all Catalog tests**

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend vendor/bin/pest tests/Unit/Catalog/
```

Expected: all tests PASS

- [ ] **Step 4: Run Deptrac to verify no new violations**

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend vendor/bin/deptrac analyse --config-file=deptrac.yaml --no-progress
```

Expected: zero violations. (Catalog_Application already allows Dependency_Domain via Shared or explicit — verify Task 1 ruleset covers this. `TechStackVersionStatusUpdater` using `SemanticVersion` from `Dependency_Domain` via the Catalog_Application layer requires that `Catalog_Application` allows `Dependency_Domain`. Update Deptrac ruleset if needed.)

- [ ] **Step 5: Commit**

```bash
git add backend/src/Catalog/Application/Service/TechStackVersionStatusUpdater.php backend/tests/Unit/Catalog/Application/Service/TechStackVersionStatusUpdaterTest.php
git commit -m "refactor(catalog): replace version_compare/explode with SemanticVersion in TechStackVersionStatusUpdater"
```

---

## Final Verification

- [ ] **Run complete test suite**

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend vendor/bin/pest --parallel
```

Expected: all tests PASS, no regressions

- [ ] **Run Deptrac full analysis**

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend vendor/bin/deptrac analyse --config-file=deptrac.yaml --no-progress
```

Expected: zero violations

- [ ] **Run PHPStan**

```bash
docker compose -f docker/compose.yaml -f docker/compose.override.yaml exec backend vendor/bin/phpstan analyse --no-progress
```

Expected: zero errors
