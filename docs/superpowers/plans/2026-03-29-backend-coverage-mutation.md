# Backend Coverage & Mutation Testing — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Raise backend test coverage from ~28% to 80%+ and mutation score (MSI) to 70%+ across all bounded contexts.

**Architecture:** Parallel work by layer (Domain, Application, Infrastructure, Presentation) within each bounded context. Priority order: Dependency → Catalog → Activity → Identity → Shared. Each task is self-contained and produces a green test suite.

**Tech Stack:** Pest PHP 4, Infection (mutation), PHPUnit 11, Symfony 8, Doctrine ORM, PostgreSQL 17

**Test conventions:**
- Pest `describe()/it()` blocks with `expect()` assertions
- Factories use static `create(array $overrides = [])` pattern
- Stubs are inline anonymous classes implementing interfaces
- No comments in test code
- Run tests via: `docker compose exec php vendor/bin/pest --filter=TestName`
- Run mutation via: `docker compose exec php vendor/bin/infection --filter=src/Context/Layer`

---

## Phase 1: Dependency Context (Priority 1)

### Task 1: Dependency Domain Models — Unit Tests

**Files:**
- Create: `tests/Unit/Dependency/Domain/Model/DependencyTest.php`
- Create: `tests/Unit/Dependency/Domain/Model/VulnerabilityTest.php`
- Create: `tests/Unit/Dependency/Domain/Model/SeverityTest.php`
- Create: `tests/Unit/Dependency/Domain/Model/RegistryStatusTest.php`
- Create: `tests/Unit/Dependency/Domain/Model/VulnerabilityStatusTest.php`
- Existing: `tests/Factory/Dependency/DependencyFactory.php`
- Existing: `tests/Factory/Dependency/VulnerabilityFactory.php`

- [ ] **Step 1: Write DependencyTest.php**

```php
<?php
declare(strict_types=1);

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\RegistryStatus;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use App\Tests\Factory\Dependency\DependencyFactory;
use Symfony\Component\Uid\Uuid;

describe('Dependency', function () {
    it('creates with all properties', function () {
        $projectId = Uuid::v7();
        $dep = Dependency::create(
            name: 'symfony/http-kernel',
            currentVersion: '7.2.0',
            latestVersion: '8.0.0',
            ltsVersion: '7.4.0',
            packageManager: PackageManager::Composer,
            type: DependencyType::Runtime,
            isOutdated: true,
            projectId: $projectId,
            repositoryUrl: 'https://github.com/symfony/http-kernel',
        );

        expect($dep->getId())->toBeInstanceOf(Uuid::class);
        expect($dep->getName())->toBe('symfony/http-kernel');
        expect($dep->getCurrentVersion())->toBe('7.2.0');
        expect($dep->getLatestVersion())->toBe('8.0.0');
        expect($dep->getLtsVersion())->toBe('7.4.0');
        expect($dep->getPackageManager())->toBe(PackageManager::Composer);
        expect($dep->getType())->toBe(DependencyType::Runtime);
        expect($dep->isOutdated())->toBeTrue();
        expect($dep->getProjectId())->toBe($projectId);
        expect($dep->getRepositoryUrl())->toBe('https://github.com/symfony/http-kernel');
        expect($dep->getRegistryStatus())->toBe(RegistryStatus::Pending);
        expect($dep->getVulnerabilities())->toBeEmpty();
        expect($dep->getVulnerabilityCount())->toBe(0);
        expect($dep->getCreatedAt())->toBeInstanceOf(DateTimeImmutable::class);
        expect($dep->getUpdatedAt())->toBeInstanceOf(DateTimeImmutable::class);
    });

    it('creates without optional repositoryUrl', function () {
        $dep = DependencyFactory::create();

        expect($dep->getRepositoryUrl())->toBeNull();
    });

    it('marks registry status', function () {
        $dep = DependencyFactory::create();
        $beforeUpdate = $dep->getUpdatedAt();

        usleep(1000);
        $dep->markRegistryStatus(RegistryStatus::Synced);

        expect($dep->getRegistryStatus())->toBe(RegistryStatus::Synced);
        expect($dep->getUpdatedAt())->not->toBe($beforeUpdate);
    });

    it('updates name only', function () {
        $dep = DependencyFactory::create();

        $dep->update(name: 'new-package');

        expect($dep->getName())->toBe('new-package');
        expect($dep->getCurrentVersion())->toBe('7.2.0');
    });

    it('updates all fields', function () {
        $dep = DependencyFactory::create();

        $dep->update(
            name: 'vue',
            currentVersion: '3.5.0',
            latestVersion: '4.0.0',
            ltsVersion: '3.4.0',
            packageManager: PackageManager::Npm,
            type: DependencyType::Dev,
            isOutdated: false,
            repositoryUrl: 'https://github.com/vuejs/core',
        );

        expect($dep->getName())->toBe('vue');
        expect($dep->getCurrentVersion())->toBe('3.5.0');
        expect($dep->getLatestVersion())->toBe('4.0.0');
        expect($dep->getLtsVersion())->toBe('3.4.0');
        expect($dep->getPackageManager())->toBe(PackageManager::Npm);
        expect($dep->getType())->toBe(DependencyType::Dev);
        expect($dep->isOutdated())->toBeFalse();
        expect($dep->getRepositoryUrl())->toBe('https://github.com/vuejs/core');
    });

    it('clears repositoryUrl with flag', function () {
        $dep = DependencyFactory::create(['repositoryUrl' => 'https://example.com']);

        $dep->update(clearRepositoryUrl: true);

        expect($dep->getRepositoryUrl())->toBeNull();
    });

    it('does not clear repositoryUrl when new value provided', function () {
        $dep = DependencyFactory::create(['repositoryUrl' => 'https://old.com']);

        $dep->update(repositoryUrl: 'https://new.com', clearRepositoryUrl: true);

        expect($dep->getRepositoryUrl())->toBe('https://new.com');
    });
});
```

- [ ] **Step 2: Run test to verify it passes**

Run: `docker compose exec php vendor/bin/pest --filter=DependencyTest tests/Unit/Dependency/Domain/Model/DependencyTest.php`
Expected: PASS (tests against existing code)

- [ ] **Step 3: Write VulnerabilityTest.php**

```php
<?php
declare(strict_types=1);

use App\Dependency\Domain\Model\Severity;
use App\Dependency\Domain\Model\Vulnerability;
use App\Dependency\Domain\Model\VulnerabilityStatus;
use App\Tests\Factory\Dependency\DependencyFactory;
use App\Tests\Factory\Dependency\VulnerabilityFactory;
use Symfony\Component\Uid\Uuid;

describe('Vulnerability', function () {
    it('creates with all properties', function () {
        $dependency = DependencyFactory::create();
        $detectedAt = new DateTimeImmutable('2026-01-15T10:00:00+00:00');

        $vuln = Vulnerability::create(
            cveId: 'CVE-2026-0001',
            severity: Severity::Critical,
            title: 'RCE in parser',
            description: 'Remote code execution vulnerability',
            patchedVersion: '8.0.1',
            status: VulnerabilityStatus::Open,
            detectedAt: $detectedAt,
            dependency: $dependency,
        );

        expect($vuln->getId())->toBeInstanceOf(Uuid::class);
        expect($vuln->getCveId())->toBe('CVE-2026-0001');
        expect($vuln->getSeverity())->toBe(Severity::Critical);
        expect($vuln->getTitle())->toBe('RCE in parser');
        expect($vuln->getDescription())->toBe('Remote code execution vulnerability');
        expect($vuln->getPatchedVersion())->toBe('8.0.1');
        expect($vuln->getStatus())->toBe(VulnerabilityStatus::Open);
        expect($vuln->getDetectedAt())->toBe($detectedAt);
        expect($vuln->getDependency())->toBe($dependency);
        expect($vuln->getCreatedAt())->toBeInstanceOf(DateTimeImmutable::class);
        expect($vuln->getUpdatedAt())->toBeInstanceOf(DateTimeImmutable::class);
    });

    it('updates single field', function () {
        $vuln = VulnerabilityFactory::create(DependencyFactory::create());

        $vuln->update(status: VulnerabilityStatus::Fixed);

        expect($vuln->getStatus())->toBe(VulnerabilityStatus::Fixed);
        expect($vuln->getCveId())->toBe('CVE-2026-12345');
    });

    it('updates all fields', function () {
        $vuln = VulnerabilityFactory::create(DependencyFactory::create());

        $vuln->update(
            cveId: 'CVE-2026-99999',
            severity: Severity::Low,
            title: 'Updated title',
            description: 'Updated description',
            patchedVersion: '9.0.0',
            status: VulnerabilityStatus::Ignored,
        );

        expect($vuln->getCveId())->toBe('CVE-2026-99999');
        expect($vuln->getSeverity())->toBe(Severity::Low);
        expect($vuln->getTitle())->toBe('Updated title');
        expect($vuln->getDescription())->toBe('Updated description');
        expect($vuln->getPatchedVersion())->toBe('9.0.0');
        expect($vuln->getStatus())->toBe(VulnerabilityStatus::Ignored);
    });
});
```

- [ ] **Step 4: Write enum tests (SeverityTest, RegistryStatusTest, VulnerabilityStatusTest)**

```php
<?php
// tests/Unit/Dependency/Domain/Model/SeverityTest.php
declare(strict_types=1);

use App\Dependency\Domain\Model\Severity;

describe('Severity', function () {
    it('has all expected cases', function () {
        expect(Severity::cases())->toHaveCount(4);
        expect(Severity::Critical->value)->toBe('critical');
        expect(Severity::High->value)->toBe('high');
        expect(Severity::Medium->value)->toBe('medium');
        expect(Severity::Low->value)->toBe('low');
    });

    it('creates from string', function () {
        expect(Severity::from('critical'))->toBe(Severity::Critical);
        expect(Severity::from('low'))->toBe(Severity::Low);
    });

    it('returns null for invalid value', function () {
        expect(Severity::tryFrom('unknown'))->toBeNull();
    });
});
```

```php
<?php
// tests/Unit/Dependency/Domain/Model/RegistryStatusTest.php
declare(strict_types=1);

use App\Dependency\Domain\Model\RegistryStatus;

describe('RegistryStatus', function () {
    it('has all expected cases', function () {
        expect(RegistryStatus::cases())->toHaveCount(3);
        expect(RegistryStatus::Pending->value)->toBe('pending');
        expect(RegistryStatus::Synced->value)->toBe('synced');
        expect(RegistryStatus::NotFound->value)->toBe('not_found');
    });

    it('creates from string', function () {
        expect(RegistryStatus::from('pending'))->toBe(RegistryStatus::Pending);
        expect(RegistryStatus::from('not_found'))->toBe(RegistryStatus::NotFound);
    });

    it('returns null for invalid value', function () {
        expect(RegistryStatus::tryFrom('invalid'))->toBeNull();
    });
});
```

```php
<?php
// tests/Unit/Dependency/Domain/Model/VulnerabilityStatusTest.php
declare(strict_types=1);

use App\Dependency\Domain\Model\VulnerabilityStatus;

describe('VulnerabilityStatus', function () {
    it('has all expected cases', function () {
        expect(VulnerabilityStatus::cases())->toHaveCount(4);
        expect(VulnerabilityStatus::Open->value)->toBe('open');
        expect(VulnerabilityStatus::Acknowledged->value)->toBe('acknowledged');
        expect(VulnerabilityStatus::Fixed->value)->toBe('fixed');
        expect(VulnerabilityStatus::Ignored->value)->toBe('ignored');
    });

    it('creates from string', function () {
        expect(VulnerabilityStatus::from('open'))->toBe(VulnerabilityStatus::Open);
        expect(VulnerabilityStatus::from('ignored'))->toBe(VulnerabilityStatus::Ignored);
    });

    it('returns null for invalid value', function () {
        expect(VulnerabilityStatus::tryFrom('removed'))->toBeNull();
    });
});
```

- [ ] **Step 5: Run all domain model tests**

Run: `docker compose exec php vendor/bin/pest tests/Unit/Dependency/Domain/ -v`
Expected: All PASS

- [ ] **Step 6: Run mutation testing on Dependency Domain**

Run: `docker compose exec php vendor/bin/infection --filter=src/Dependency/Domain/Model --threads=4 --show-mutations`
Expected: Review surviving mutants, strengthen tests in next step

- [ ] **Step 7: Commit**

```bash
git add tests/Unit/Dependency/Domain/Model/
git commit -m "test(dependency): add domain model unit tests — Dependency, Vulnerability, enums"
```

---

### Task 2: Dependency Domain Events & DTO — Unit Tests

**Files:**
- Create: `tests/Unit/Dependency/Domain/Event/DependencyEventsTest.php`
- Create: `tests/Unit/Dependency/Domain/DTO/RegistryVersionTest.php`

- [ ] **Step 1: Write DependencyEventsTest.php**

```php
<?php
declare(strict_types=1);

use App\Dependency\Domain\Event\DependencyCreated;
use App\Dependency\Domain\Event\DependencyDeleted;
use App\Dependency\Domain\Event\DependencyUpdated;

describe('DependencyCreated', function () {
    it('holds dependency id and name', function () {
        $event = new DependencyCreated(dependencyId: 'dep-1', name: 'symfony/http-kernel');

        expect($event->dependencyId)->toBe('dep-1');
        expect($event->name)->toBe('symfony/http-kernel');
    });
});

describe('DependencyDeleted', function () {
    it('holds dependency id and name', function () {
        $event = new DependencyDeleted(dependencyId: 'dep-1', name: 'vue');

        expect($event->dependencyId)->toBe('dep-1');
        expect($event->name)->toBe('vue');
    });
});

describe('DependencyUpdated', function () {
    it('holds dependency id and name', function () {
        $event = new DependencyUpdated(dependencyId: 'dep-1', name: 'react');

        expect($event->dependencyId)->toBe('dep-1');
        expect($event->name)->toBe('react');
    });
});
```

- [ ] **Step 2: Write RegistryVersionTest.php**

```php
<?php
declare(strict_types=1);

use App\Dependency\Domain\DTO\RegistryVersion;

describe('RegistryVersion', function () {
    it('creates with all properties', function () {
        $date = new DateTimeImmutable('2026-06-15');
        $rv = new RegistryVersion(version: '3.5.0', releaseDate: $date, isLatest: true);

        expect($rv->version)->toBe('3.5.0');
        expect($rv->releaseDate)->toBe($date);
        expect($rv->isLatest)->toBeTrue();
    });

    it('creates with defaults', function () {
        $rv = new RegistryVersion(version: '1.0.0');

        expect($rv->version)->toBe('1.0.0');
        expect($rv->releaseDate)->toBeNull();
        expect($rv->isLatest)->toBeFalse();
    });
});
```

- [ ] **Step 3: Run tests**

Run: `docker compose exec php vendor/bin/pest tests/Unit/Dependency/Domain/ -v`
Expected: All PASS

- [ ] **Step 4: Commit**

```bash
git add tests/Unit/Dependency/Domain/Event/ tests/Unit/Dependency/Domain/DTO/
git commit -m "test(dependency): add domain event and DTO tests"
```

---

### Task 3: Dependency Infrastructure — PackageRegistryFactory Test

**Files:**
- Create: `tests/Unit/Dependency/Infrastructure/Registry/PackageRegistryFactoryTest.php`

- [ ] **Step 1: Write PackageRegistryFactoryTest.php**

```php
<?php
declare(strict_types=1);

use App\Dependency\Domain\DTO\RegistryVersion;
use App\Dependency\Domain\Port\PackageRegistryPort;
use App\Dependency\Infrastructure\Registry\PackageRegistryFactory;
use App\Shared\Domain\ValueObject\PackageManager;

describe('PackageRegistryFactory', function () {
    it('delegates to the adapter that supports the package manager', function () {
        $composerAdapter = new class implements PackageRegistryPort {
            public function supports(PackageManager $manager): bool
            {
                return $manager === PackageManager::Composer;
            }
            public function fetchVersions(string $packageName, PackageManager $manager, ?string $sinceVersion = null): array
            {
                return [new RegistryVersion(version: '8.0.0', isLatest: true)];
            }
        };

        $factory = new PackageRegistryFactory([$composerAdapter]);
        $versions = $factory->fetchVersions('symfony/http-kernel', PackageManager::Composer);

        expect($versions)->toHaveCount(1);
        expect($versions[0]->version)->toBe('8.0.0');
        expect($versions[0]->isLatest)->toBeTrue();
    });

    it('returns empty array when no adapter supports the manager', function () {
        $composerAdapter = new class implements PackageRegistryPort {
            public function supports(PackageManager $manager): bool
            {
                return $manager === PackageManager::Composer;
            }
            public function fetchVersions(string $packageName, PackageManager $manager, ?string $sinceVersion = null): array
            {
                return [new RegistryVersion(version: '1.0.0')];
            }
        };

        $factory = new PackageRegistryFactory([$composerAdapter]);
        $versions = $factory->fetchVersions('vue', PackageManager::Npm);

        expect($versions)->toBeEmpty();
    });

    it('uses first matching adapter', function () {
        $first = new class implements PackageRegistryPort {
            public function supports(PackageManager $manager): bool
            {
                return $manager === PackageManager::Npm;
            }
            public function fetchVersions(string $packageName, PackageManager $manager, ?string $sinceVersion = null): array
            {
                return [new RegistryVersion(version: 'first')];
            }
        };
        $second = new class implements PackageRegistryPort {
            public function supports(PackageManager $manager): bool
            {
                return $manager === PackageManager::Npm;
            }
            public function fetchVersions(string $packageName, PackageManager $manager, ?string $sinceVersion = null): array
            {
                return [new RegistryVersion(version: 'second')];
            }
        };

        $factory = new PackageRegistryFactory([$first, $second]);
        $versions = $factory->fetchVersions('vue', PackageManager::Npm);

        expect($versions[0]->version)->toBe('first');
    });

    it('passes sinceVersion to the adapter', function () {
        $adapter = new class implements PackageRegistryPort {
            public ?string $receivedSince = null;
            public function supports(PackageManager $manager): bool
            {
                return true;
            }
            public function fetchVersions(string $packageName, PackageManager $manager, ?string $sinceVersion = null): array
            {
                $this->receivedSince = $sinceVersion;
                return [];
            }
        };

        $factory = new PackageRegistryFactory([$adapter]);
        $factory->fetchVersions('pkg', PackageManager::Composer, '7.0.0');

        expect($adapter->receivedSince)->toBe('7.0.0');
    });
});
```

- [ ] **Step 2: Run test**

Run: `docker compose exec php vendor/bin/pest tests/Unit/Dependency/Infrastructure/Registry/PackageRegistryFactoryTest.php -v`
Expected: All PASS

- [ ] **Step 3: Run mutation on registry**

Run: `docker compose exec php vendor/bin/infection --filter=src/Dependency/Infrastructure/Registry --threads=4 --show-mutations`

- [ ] **Step 4: Commit**

```bash
git add tests/Unit/Dependency/Infrastructure/Registry/PackageRegistryFactoryTest.php
git commit -m "test(dependency): add PackageRegistryFactory unit test"
```

---

### Task 4: Dependency Presentation — Individual Controller Tests

**Files:**
- Create: `tests/Unit/Dependency/Presentation/Controller/GetDependencyStatsControllerTest.php`
- Create: `tests/Unit/Dependency/Presentation/Controller/SyncDependencyVersionsControllerTest.php`

Note: The existing `DependencyControllersTest.php` already covers Create, Get, Update, Delete, List for both Dependencies and Vulnerabilities. We add the two missing controllers here.

- [ ] **Step 1: Write GetDependencyStatsControllerTest.php**

```php
<?php
declare(strict_types=1);

use App\Dependency\Application\DTO\DependencyStatsOutput;
use App\Dependency\Application\Query\GetDependencyStatsQuery;
use App\Dependency\Presentation\Controller\GetDependencyStatsController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

describe('GetDependencyStatsController', function () {
    it('returns stats with filters', function () {
        $output = new DependencyStatsOutput(total: 10, upToDate: 6, outdated: 4, totalVulnerabilities: 3);
        $bus = new class ($output) extends stdClass implements MessageBusInterface {
            public ?object $dispatched = null;
            public function __construct(private readonly mixed $result) {}
            public function dispatch(object $message, array $stamps = []): Envelope
            {
                $this->dispatched = $message;
                return (new Envelope($message))->with(new HandledStamp($this->result, 'handler'));
            }
        };

        $request = Request::create('/api/dependency/stats', 'GET', [
            'project_id' => 'p-1',
            'package_manager' => 'composer',
            'type' => 'runtime',
        ]);
        $response = (new GetDependencyStatsController($bus))($request);

        expect($response->getStatusCode())->toBe(200);
        expect($bus->dispatched)->toBeInstanceOf(GetDependencyStatsQuery::class);
        expect($bus->dispatched->projectId)->toBe('p-1');
        expect($bus->dispatched->packageManager)->toBe('composer');
        expect($bus->dispatched->type)->toBe('runtime');
    });
});
```

- [ ] **Step 2: Write SyncDependencyVersionsControllerTest.php**

```php
<?php
declare(strict_types=1);

use App\Dependency\Application\Command\SyncDependencyVersionsCommand;
use App\Dependency\Presentation\Controller\SyncDependencyVersionsController;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

describe('SyncDependencyVersionsController', function () {
    it('dispatches sync command and returns 202', function () {
        $bus = new class extends stdClass implements MessageBusInterface {
            public ?object $dispatched = null;
            public function dispatch(object $message, array $stamps = []): Envelope
            {
                $this->dispatched = $message;
                return new Envelope($message);
            }
        };

        $response = (new SyncDependencyVersionsController($bus))();

        expect($response->getStatusCode())->toBe(202);
        expect($bus->dispatched)->toBeInstanceOf(SyncDependencyVersionsCommand::class);
        expect($bus->dispatched->syncId)->toBeString()->not->toBeEmpty();
    });
});
```

- [ ] **Step 3: Run all Dependency tests**

Run: `docker compose exec php vendor/bin/pest tests/Unit/Dependency/ -v`
Expected: All PASS

- [ ] **Step 4: Commit**

```bash
git add tests/Unit/Dependency/Presentation/Controller/GetDependencyStatsControllerTest.php tests/Unit/Dependency/Presentation/Controller/SyncDependencyVersionsControllerTest.php
git commit -m "test(dependency): add missing controller tests — stats, sync"
```

---

### Task 5: Dependency — Mutation Hardening

- [ ] **Step 1: Run full Infection on Dependency context**

Run: `docker compose exec php vendor/bin/infection --filter=src/Dependency --threads=4 --show-mutations --min-msi=60`

- [ ] **Step 2: Review surviving mutants and strengthen tests**

Focus on:
- Boundary conditions in `update()` methods (null vs non-null)
- Return values and status transitions
- Registry factory delegation logic

- [ ] **Step 3: Run Infection again to verify MSI improvement**

Run: `docker compose exec php vendor/bin/infection --filter=src/Dependency --threads=4 --min-msi=70`
Expected: MSI >= 70%

- [ ] **Step 4: Commit strengthened tests**

```bash
git add tests/Unit/Dependency/
git commit -m "test(dependency): harden tests against mutation — MSI 70%+"
```

---

## Phase 2: Catalog Context (Priority 2)

### Task 6: Catalog Domain Models — Unit Tests

**Files:**
- Create: `tests/Unit/Catalog/Domain/Model/ProjectTest.php`
- Create: `tests/Unit/Catalog/Domain/Model/ProjectVisibilityTest.php`
- Create: `tests/Unit/Catalog/Domain/Model/ProviderTest.php`
- Create: `tests/Unit/Catalog/Domain/Model/ProviderStatusTest.php`
- Create: `tests/Unit/Catalog/Domain/Model/ProviderTypeTest.php`
- Create: `tests/Unit/Catalog/Domain/Model/SyncJobTest.php`
- Create: `tests/Unit/Catalog/Domain/Model/SyncJobStatusTest.php`
- Create: `tests/Unit/Catalog/Domain/Model/TechStackTest.php`
- Create: `tests/Unit/Catalog/Domain/Model/RemoteProjectTest.php`
- Create: `tests/Unit/Catalog/Domain/Model/RemoteMergeRequestTest.php`

- [ ] **Step 1: Read source files to understand model structure**

Read all source model files in `src/Catalog/Domain/Model/` to understand `create()` signatures, getters, and `update()` methods.

- [ ] **Step 2: Write tests for each model following the pattern from Task 1**

For each model:
- Test `create()` with all properties
- Test `create()` with defaults
- Test `update()` partial and full
- Test enum cases (for Status/Type enums)

Use existing factories: `ProjectFactory`, `ProviderFactory`, `TechStackFactory`

- [ ] **Step 3: Run tests**

Run: `docker compose exec php vendor/bin/pest tests/Unit/Catalog/Domain/ -v`
Expected: All PASS

- [ ] **Step 4: Commit**

```bash
git add tests/Unit/Catalog/Domain/
git commit -m "test(catalog): add domain model unit tests — Project, Provider, TechStack, SyncJob, enums"
```

---

### Task 7: Catalog Domain Events — Unit Tests

**Files:**
- Create: `tests/Unit/Catalog/Domain/Event/CatalogEventsTest.php`

- [ ] **Step 1: Read event source files**

Read all files in `src/Catalog/Domain/Event/` to get constructor signatures.

- [ ] **Step 2: Write tests for all 5 events**

Test each event's constructor and public properties following the pattern from Task 2.

- [ ] **Step 3: Run tests and commit**

Run: `docker compose exec php vendor/bin/pest tests/Unit/Catalog/Domain/Event/ -v`

```bash
git add tests/Unit/Catalog/Domain/Event/
git commit -m "test(catalog): add domain event tests"
```

---

### Task 8: Catalog Application — TestProviderConnectionHandler Test

**Files:**
- Create: `tests/Unit/Catalog/Application/CommandHandler/TestProviderConnectionHandlerTest.php`

- [ ] **Step 1: Read TestProviderConnectionHandler source**

Read `src/Catalog/Application/CommandHandler/TestProviderConnectionHandler.php` to understand dependencies and logic.

- [ ] **Step 2: Write test following existing handler test patterns**

Use existing handler test files as reference for stub patterns.

- [ ] **Step 3: Run test and commit**

Run: `docker compose exec php vendor/bin/pest tests/Unit/Catalog/Application/CommandHandler/TestProviderConnectionHandlerTest.php -v`

```bash
git add tests/Unit/Catalog/Application/CommandHandler/TestProviderConnectionHandlerTest.php
git commit -m "test(catalog): add TestProviderConnectionHandler test"
```

---

### Task 9: Catalog Presentation — Granular Controller Tests

**Files:**
- Review existing consolidated tests: `ProjectControllersTest.php`, `ProviderControllersTest.php`, `ResourceControllersTest.php`, `SyncControllersTest.php`

Note: The consolidated tests already cover all controllers. This task focuses on verifying coverage is adequate and adding edge cases if needed.

- [ ] **Step 1: Review consolidated tests for completeness**

Read all consolidated test files and verify each controller has at least one test exercising the main path.

- [ ] **Step 2: Add missing edge case tests if any controllers lack coverage**

Focus on controllers that may have conditional logic not tested.

- [ ] **Step 3: Run and commit**

Run: `docker compose exec php vendor/bin/pest tests/Unit/Catalog/Presentation/ -v`

```bash
git add tests/Unit/Catalog/Presentation/
git commit -m "test(catalog): strengthen controller tests"
```

---

### Task 10: Catalog — Mutation Hardening

- [ ] **Step 1: Run Infection on Catalog context**

Run: `docker compose exec php vendor/bin/infection --filter=src/Catalog --threads=4 --show-mutations`

- [ ] **Step 2: Strengthen tests to kill surviving mutants**

- [ ] **Step 3: Verify MSI >= 70%**

Run: `docker compose exec php vendor/bin/infection --filter=src/Catalog --threads=4 --min-msi=70`

- [ ] **Step 4: Commit**

```bash
git add tests/Unit/Catalog/
git commit -m "test(catalog): harden tests against mutation — MSI 70%+"
```

---

## Phase 3: Activity Context (Priority 3)

### Task 11: Activity Domain Models — Unit Tests

**Files:**
- Create: `tests/Unit/Activity/Domain/Model/ActivityEventTest.php`
- Create: `tests/Unit/Activity/Domain/Model/NotificationTest.php`
- Create: `tests/Unit/Activity/Domain/Model/NotificationChannelTest.php`
- Create: `tests/Unit/Activity/Domain/Model/SyncTaskTest.php`
- Create: `tests/Unit/Activity/Domain/Model/SyncTaskSeverityTest.php`
- Create: `tests/Unit/Activity/Domain/Model/SyncTaskStatusTest.php`
- Create: `tests/Unit/Activity/Domain/Model/SyncTaskTypeTest.php`

- [ ] **Step 1: Read all Activity domain model source files**

- [ ] **Step 2: Write tests for each model and enum**

Follow patterns from Tasks 1 and 4. Use existing `ActivityEventFactory` and `NotificationFactory`.

- [ ] **Step 3: Run tests and commit**

Run: `docker compose exec php vendor/bin/pest tests/Unit/Activity/Domain/ -v`

```bash
git add tests/Unit/Activity/Domain/
git commit -m "test(activity): add domain model unit tests — ActivityEvent, Notification, SyncTask, enums"
```

---

### Task 12: Activity Missing Query Handlers — Unit Tests

**Files:**
- Create: `tests/Unit/Activity/Application/QueryHandler/GetLatestBuildMetricHandlerTest.php`
- Create: `tests/Unit/Activity/Application/QueryHandler/GetMessengerStatsHandlerTest.php`
- Create: `tests/Unit/Activity/Application/QueryHandler/GetSyncTaskStatsHandlerTest.php`

Note: Check if `StatsHandlersTest.php` already covers these — if so, verify and skip.

- [ ] **Step 1: Read source files for the 3 query handlers**

- [ ] **Step 2: Check existing StatsHandlersTest.php coverage**

If already covered, skip creation and note.

- [ ] **Step 3: Write missing tests following existing handler test patterns**

- [ ] **Step 4: Run and commit**

Run: `docker compose exec php vendor/bin/pest tests/Unit/Activity/Application/QueryHandler/ -v`

```bash
git add tests/Unit/Activity/Application/QueryHandler/
git commit -m "test(activity): add missing query handler tests"
```

---

### Task 13: Activity Presentation — Console Command Test

**Files:**
- Create: `tests/Unit/Activity/Presentation/Command/PublishMessengerStatsCommandTest.php`

- [ ] **Step 1: Read source file**

Read `src/Activity/Presentation/Command/PublishMessengerStatsCommand.php`

- [ ] **Step 2: Write test for console command I/O**

- [ ] **Step 3: Run and commit**

```bash
git add tests/Unit/Activity/Presentation/Command/
git commit -m "test(activity): add PublishMessengerStatsCommand test"
```

---

### Task 14: Activity — Mutation Hardening

- [ ] **Step 1: Run Infection on Activity context**

Run: `docker compose exec php vendor/bin/infection --filter=src/Activity --threads=4 --show-mutations`

- [ ] **Step 2: Strengthen tests to kill surviving mutants**

- [ ] **Step 3: Verify MSI >= 70% and commit**

```bash
git add tests/Unit/Activity/
git commit -m "test(activity): harden tests against mutation — MSI 70%+"
```

---

## Phase 4: Identity Context (Priority 4)

### Task 15: Identity Domain Models — Unit Tests

**Files:**
- Create: `tests/Unit/Identity/Domain/Model/AccessTokenTest.php`
- Create: `tests/Unit/Identity/Domain/Model/TokenProviderTest.php`

- [ ] **Step 1: Read source files**

- [ ] **Step 2: Write tests (User already tested in existing UserTest.php)**

- [ ] **Step 3: Run and commit**

```bash
git add tests/Unit/Identity/Domain/
git commit -m "test(identity): add AccessToken and TokenProvider domain tests"
```

---

### Task 16: Identity Domain Events — Unit Tests

**Files:**
- Create: `tests/Unit/Identity/Domain/Event/IdentityEventsTest.php`

- [ ] **Step 1: Write tests for UserCreated and UserUpdated events**

- [ ] **Step 2: Run and commit**

```bash
git add tests/Unit/Identity/Domain/Event/
git commit -m "test(identity): add domain event tests"
```

---

### Task 17: Identity — Mutation Hardening

- [ ] **Step 1: Run Infection on Identity context**
- [ ] **Step 2: Strengthen tests**
- [ ] **Step 3: Verify MSI >= 70% and commit**

```bash
git add tests/Unit/Identity/
git commit -m "test(identity): harden tests against mutation — MSI 70%+"
```

---

## Phase 5: Shared Context (Priority 5)

### Task 18: Shared Domain — Value Objects & DTOs Tests

**Files:**
- Create: `tests/Unit/Shared/Domain/ValueObject/DependencyTypeTest.php`
- Create: `tests/Unit/Shared/Domain/ValueObject/PackageManagerTest.php`
- Create: `tests/Unit/Shared/Domain/DTO/SharedDTOsTest.php`
- Create: `tests/Unit/Shared/Domain/Event/SharedEventsTest.php`
- Create: `tests/Unit/Shared/Domain/Exception/SharedExceptionsTest.php`

- [ ] **Step 1: Write DependencyTypeTest.php**

```php
<?php
declare(strict_types=1);

use App\Shared\Domain\ValueObject\DependencyType;

describe('DependencyType', function () {
    it('has runtime and dev cases', function () {
        expect(DependencyType::cases())->toHaveCount(2);
        expect(DependencyType::Runtime->value)->toBe('runtime');
        expect(DependencyType::Dev->value)->toBe('dev');
    });

    it('creates from string', function () {
        expect(DependencyType::from('runtime'))->toBe(DependencyType::Runtime);
        expect(DependencyType::from('dev'))->toBe(DependencyType::Dev);
    });

    it('returns null for invalid value', function () {
        expect(DependencyType::tryFrom('test'))->toBeNull();
    });
});
```

- [ ] **Step 2: Write PackageManagerTest.php**

```php
<?php
declare(strict_types=1);

use App\Shared\Domain\ValueObject\PackageManager;

describe('PackageManager', function () {
    it('has all package manager cases', function () {
        expect(PackageManager::cases())->toHaveCount(3);
        expect(PackageManager::Composer->value)->toBe('composer');
        expect(PackageManager::Npm->value)->toBe('npm');
        expect(PackageManager::Pip->value)->toBe('pip');
    });

    it('creates from string', function () {
        expect(PackageManager::from('composer'))->toBe(PackageManager::Composer);
        expect(PackageManager::from('npm'))->toBe(PackageManager::Npm);
        expect(PackageManager::from('pip'))->toBe(PackageManager::Pip);
    });

    it('returns null for invalid value', function () {
        expect(PackageManager::tryFrom('yarn'))->toBeNull();
    });
});
```

- [ ] **Step 3: Write Shared DTOs, Events, and Exceptions tests**

Read source files, then write tests for each readonly class/DTO constructor.

- [ ] **Step 4: Run and commit**

Run: `docker compose exec php vendor/bin/pest tests/Unit/Shared/ -v`

```bash
git add tests/Unit/Shared/
git commit -m "test(shared): add value object, DTO, event, and exception tests"
```

---

### Task 19: Shared Infrastructure — Missing Tests

**Files:**
- Create: `tests/Unit/Shared/Infrastructure/Controller/HealthControllersTest.php`
- Create: `tests/Unit/Shared/Infrastructure/EventListener/SecurityHeadersListenerTest.php`

- [ ] **Step 1: Read source files**

- [ ] **Step 2: Write tests**

- [ ] **Step 3: Run and commit**

```bash
git add tests/Unit/Shared/Infrastructure/
git commit -m "test(shared): add Health controller and SecurityHeaders listener tests"
```

---

## Phase 6: Final Validation

### Task 20: Full Test Suite + Coverage Report

- [ ] **Step 1: Run full test suite**

Run: `docker compose exec php vendor/bin/pest -v`
Expected: All tests PASS, no regressions

- [ ] **Step 2: Run coverage report**

Run: `docker compose exec php vendor/bin/pest --coverage --min=80`
Expected: Line coverage >= 80%

- [ ] **Step 3: Run full mutation testing**

Run: `docker compose exec php vendor/bin/infection --threads=4 --min-msi=60 --min-covered-msi=80`
Expected: MSI >= 70%, Covered MSI >= 80%

- [ ] **Step 4: Final commit**

```bash
git add -A
git commit -m "test: achieve 80%+ coverage and 70%+ mutation score across all contexts"
```
