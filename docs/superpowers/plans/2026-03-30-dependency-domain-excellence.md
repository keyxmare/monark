# Dependency Domain Excellence — Phase 1: Domain Layer

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Enrich the Dependency bounded context domain layer with rich Value Objects, Domain Services with advanced design patterns, and dual-purpose Specifications to create a DDD showcase.

**Architecture:** Bottom-up — Value Objects first, then Domain Services that compose them, then Aggregate Root enrichment, then Specifications. Each component is independently testable. The entity keeps string persistence but exposes typed domain access.

**Tech Stack:** PHP 8.4, Symfony 8, Pest 4, Doctrine ORM 3.4

**Spec:** `docs/superpowers/specs/2026-03-30-dependency-context-excellence-design.md` (Sections 1-3)

**Runtime constraint:** All commands run via `docker compose exec backend ...` (never bare php/composer).

**Test conventions:**
- Pest `describe/it` syntax, `expect()` fluent assertions
- Anonymous class stubs (no mocking library), helper functions at file top with `\` prefix calls
- No `beforeEach` — inline setup per test

---

## File Map

### New Files — Value Objects
- `backend/src/Dependency/Domain/ValueObject/SemanticVersion.php` — Semver parsing, comparison, gaps
- `backend/src/Dependency/Domain/ValueObject/CveId.php` — CVE identifier validation
- `backend/src/Dependency/Domain/ValueObject/RiskLevel.php` — Risk level enum
- `backend/src/Dependency/Domain/ValueObject/VulnerabilityScore.php` — CVSS-like scoring
- `backend/src/Dependency/Domain/ValueObject/DependencyHealth.php` — Composite health scoring
- `backend/src/Dependency/Domain/ValueObject/RiskAssessment.php` — Assessment result

### New Files — Domain Services
- `backend/src/Dependency/Domain/Service/VersionComparisonService.php` — Orchestrates version comparison
- `backend/src/Dependency/Domain/Service/Strategy/VersionStrategyInterface.php` — Strategy contract
- `backend/src/Dependency/Domain/Service/Strategy/NpmVersionStrategy.php` — npm semver rules
- `backend/src/Dependency/Domain/Service/Strategy/ComposerVersionStrategy.php` — Composer semver rules
- `backend/src/Dependency/Domain/Service/Strategy/PipVersionStrategy.php` — PEP 440 rules
- `backend/src/Dependency/Domain/Service/DependencyHealthCalculator.php` — Health score calculation
- `backend/src/Dependency/Domain/Service/VulnerabilityAssessor.php` — Risk assessment orchestrator
- `backend/src/Dependency/Domain/Service/Assessment/AssessmentHandlerInterface.php` — CoR contract
- `backend/src/Dependency/Domain/Service/Assessment/SeverityHandler.php` — Severity-based assessment
- `backend/src/Dependency/Domain/Service/Assessment/PatchAvailabilityHandler.php` — Patch-based assessment
- `backend/src/Dependency/Domain/Service/Assessment/AgeHandler.php` — Age-based assessment

### New Files — Domain Events
- `backend/src/Dependency/Domain/Event/DependencyUpgraded.php` — Rich upgrade event
- `backend/src/Dependency/Domain/Event/VulnerabilityDetected.php` — Rich detection event
- `backend/src/Dependency/Domain/Event/VulnerabilityResolved.php` — Resolution event
- `backend/src/Dependency/Domain/Event/DependencyHealthChanged.php` — Health transition event

### New Files — Specifications
- `backend/src/Dependency/Domain/Specification/HasVersionGapAboveSpecification.php`
- `backend/src/Dependency/Domain/Specification/HasUnpatchedVulnerabilitySpecification.php`
- `backend/src/Dependency/Domain/Specification/HasSeverityAboveSpecification.php`
- `backend/src/Dependency/Domain/Specification/IsStaleSpecification.php`
- `backend/src/Dependency/Domain/Specification/BelongsToProjectSpecification.php`
- `backend/src/Dependency/Domain/Specification/HealthBelowSpecification.php`

### New Files — Shared
- `backend/src/Shared/Domain/Model/RecordsDomainEvents.php` — Event recording trait
- `backend/src/Shared/Domain/Specification/QueryableSpecificationInterface.php` — Adds Doctrine criteria

### New Files — Tests
- `backend/tests/Unit/Dependency/Domain/ValueObject/SemanticVersionTest.php`
- `backend/tests/Unit/Dependency/Domain/ValueObject/CveIdTest.php`
- `backend/tests/Unit/Dependency/Domain/ValueObject/VulnerabilityScoreTest.php`
- `backend/tests/Unit/Dependency/Domain/ValueObject/DependencyHealthTest.php`
- `backend/tests/Unit/Dependency/Domain/Service/VersionComparisonServiceTest.php`
- `backend/tests/Unit/Dependency/Domain/Service/VulnerabilityAssessorTest.php`
- `backend/tests/Unit/Dependency/Domain/Service/DependencyHealthCalculatorTest.php`
- `backend/tests/Unit/Dependency/Domain/Model/DependencyAggregateTest.php`
- `backend/tests/Unit/Dependency/Domain/Specification/SpecificationsTest.php`

### Modified Files
- `backend/src/Dependency/Domain/Model/Dependency.php` — Add business methods, event recording
- `backend/src/Dependency/Domain/Model/Vulnerability.php` — Add CveId VO access, resolve method
- `backend/src/Dependency/Domain/Event/DependencyCreated.php` — Enrich with context
- `backend/src/Dependency/Domain/Event/DependencyUpdated.php` — Enrich with context
- `backend/src/Dependency/Domain/Event/DependencyDeleted.php` — Enrich with context
- `backend/src/Dependency/Domain/Specification/IsOutdatedSpecification.php` — Add Doctrine criteria
- `backend/src/Dependency/Domain/Specification/IsDeprecatedSpecification.php` — Add Doctrine criteria
- `backend/src/Dependency/Domain/Specification/HasCriticalVulnerabilitySpecification.php` — Add Doctrine criteria
- `backend/src/Shared/Domain/Specification/AndSpecification.php` — Add Doctrine criteria support
- `backend/src/Shared/Domain/Specification/OrSpecification.php` — Add Doctrine criteria support
- `backend/src/Shared/Domain/Specification/NotSpecification.php` — Add Doctrine criteria support

---

### Task 1: RecordsDomainEvents trait

**Files:**
- Create: `backend/src/Shared/Domain/Model/RecordsDomainEvents.php`
- Test: `backend/tests/Unit/Shared/Domain/Model/RecordsDomainEventsTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Shared\Domain\Model\RecordsDomainEvents;

function createRecordingEntity(): object
{
    return new class () {
        use RecordsDomainEvents;

        public function doSomething(): void
        {
            $this->recordEvent(new \stdClass());
        }
    };
}

describe('RecordsDomainEvents', function () {
    it('starts with no events', function () {
        $entity = \createRecordingEntity();

        expect($entity->pullDomainEvents())->toBeEmpty();
    });

    it('records events', function () {
        $entity = \createRecordingEntity();
        $entity->doSomething();
        $entity->doSomething();

        expect($entity->pullDomainEvents())->toHaveCount(2);
    });

    it('clears events after pull', function () {
        $entity = \createRecordingEntity();
        $entity->doSomething();
        $entity->pullDomainEvents();

        expect($entity->pullDomainEvents())->toBeEmpty();
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Shared/Domain/Model/RecordsDomainEventsTest.php`
Expected: FAIL — trait not found

- [ ] **Step 3: Write the implementation**

```php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model;

trait RecordsDomainEvents
{
    /** @var list<object> */
    private array $domainEvents = [];

    protected function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /** @return list<object> */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Shared/Domain/Model/RecordsDomainEventsTest.php`
Expected: 3 tests PASS

- [ ] **Step 5: Commit**

```bash
git add backend/src/Shared/Domain/Model/RecordsDomainEvents.php backend/tests/Unit/Shared/Domain/Model/RecordsDomainEventsTest.php
git commit -m "feat(shared): add RecordsDomainEvents trait for aggregate root event recording"
```

---

### Task 2: SemanticVersion Value Object

**Files:**
- Create: `backend/src/Dependency/Domain/ValueObject/SemanticVersion.php`
- Test: `backend/tests/Unit/Dependency/Domain/ValueObject/SemanticVersionTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Dependency\Domain\ValueObject\SemanticVersion;

describe('SemanticVersion', function () {
    describe('parse', function () {
        it('parses standard semver', function () {
            $v = SemanticVersion::parse('1.2.3');

            expect($v->major)->toBe(1)
                ->and($v->minor)->toBe(2)
                ->and($v->patch)->toBe(3)
                ->and($v->preRelease)->toBeNull();
        });

        it('parses with v prefix', function () {
            $v = SemanticVersion::parse('v2.0.1');

            expect($v->major)->toBe(2)
                ->and($v->minor)->toBe(0)
                ->and($v->patch)->toBe(1);
        });

        it('parses with pre-release suffix', function () {
            $v = SemanticVersion::parse('1.0.0-beta.1');

            expect($v->preRelease)->toBe('beta.1');
        });

        it('normalizes two-segment versions', function () {
            $v = SemanticVersion::parse('1.2');

            expect($v->patch)->toBe(0);
        });

        it('throws on invalid format', function () {
            SemanticVersion::parse('not-a-version');
        })->throws(\InvalidArgumentException::class);

        it('throws on empty string', function () {
            SemanticVersion::parse('');
        })->throws(\InvalidArgumentException::class);
    });

    describe('isNewerThan', function () {
        it('detects newer major', function () {
            $v1 = SemanticVersion::parse('2.0.0');
            $v2 = SemanticVersion::parse('1.9.9');

            expect($v1->isNewerThan($v2))->toBeTrue();
        });

        it('detects newer minor', function () {
            $v1 = SemanticVersion::parse('1.3.0');
            $v2 = SemanticVersion::parse('1.2.9');

            expect($v1->isNewerThan($v2))->toBeTrue();
        });

        it('detects newer patch', function () {
            $v1 = SemanticVersion::parse('1.0.2');
            $v2 = SemanticVersion::parse('1.0.1');

            expect($v1->isNewerThan($v2))->toBeTrue();
        });

        it('release is newer than pre-release of same version', function () {
            $release = SemanticVersion::parse('1.0.0');
            $preRelease = SemanticVersion::parse('1.0.0-beta.1');

            expect($release->isNewerThan($preRelease))->toBeTrue();
            expect($preRelease->isNewerThan($release))->toBeFalse();
        });

        it('is irreflexive', function () {
            $v = SemanticVersion::parse('1.2.3');

            expect($v->isNewerThan($v))->toBeFalse();
        });

        it('is transitive', function () {
            $a = SemanticVersion::parse('3.0.0');
            $b = SemanticVersion::parse('2.0.0');
            $c = SemanticVersion::parse('1.0.0');

            expect($a->isNewerThan($b))->toBeTrue()
                ->and($b->isNewerThan($c))->toBeTrue()
                ->and($a->isNewerThan($c))->toBeTrue();
        });

        it('compares pre-release segments numerically', function () {
            $v1 = SemanticVersion::parse('1.0.0-alpha.10');
            $v2 = SemanticVersion::parse('1.0.0-alpha.2');

            expect($v1->isNewerThan($v2))->toBeTrue();
        });

        it('orders pre-release alphabetically when not numeric', function () {
            $beta = SemanticVersion::parse('1.0.0-beta');
            $alpha = SemanticVersion::parse('1.0.0-alpha');

            expect($beta->isNewerThan($alpha))->toBeTrue();
        });
    });

    describe('isCompatibleWith', function () {
        it('same major is compatible', function () {
            $v1 = SemanticVersion::parse('1.2.3');
            $v2 = SemanticVersion::parse('1.9.0');

            expect($v1->isCompatibleWith($v2))->toBeTrue();
        });

        it('different major is incompatible', function () {
            $v1 = SemanticVersion::parse('1.0.0');
            $v2 = SemanticVersion::parse('2.0.0');

            expect($v1->isCompatibleWith($v2))->toBeFalse();
        });
    });

    describe('gaps', function () {
        it('calculates major gap', function () {
            $current = SemanticVersion::parse('1.0.0');
            $latest = SemanticVersion::parse('3.2.1');

            expect($current->getMajorGap($latest))->toBe(2);
        });

        it('calculates minor gap', function () {
            $current = SemanticVersion::parse('1.0.0');
            $latest = SemanticVersion::parse('1.5.0');

            expect($current->getMinorGap($latest))->toBe(5);
        });

        it('calculates patch gap', function () {
            $current = SemanticVersion::parse('1.0.0');
            $latest = SemanticVersion::parse('1.0.7');

            expect($current->getPatchGap($latest))->toBe(7);
        });
    });

    describe('isPreRelease', function () {
        it('returns true for pre-release', function () {
            expect(SemanticVersion::parse('1.0.0-rc.1')->isPreRelease())->toBeTrue();
        });

        it('returns false for stable', function () {
            expect(SemanticVersion::parse('1.0.0')->isPreRelease())->toBeFalse();
        });
    });

    describe('equality and roundtrip', function () {
        it('equals identical version', function () {
            $v1 = SemanticVersion::parse('1.2.3-beta.1');
            $v2 = SemanticVersion::parse('1.2.3-beta.1');

            expect($v1->equals($v2))->toBeTrue();
        });

        it('roundtrips through string', function () {
            $original = SemanticVersion::parse('1.2.3-beta.1');
            $roundtrip = SemanticVersion::parse((string) $original);

            expect($roundtrip->equals($original))->toBeTrue();
        });

        it('serializes to JSON as string', function () {
            $v = SemanticVersion::parse('1.2.3');

            expect($v->jsonSerialize())->toBe('1.2.3');
        });
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Dependency/Domain/ValueObject/SemanticVersionTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Write the implementation**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\ValueObject;

final readonly class SemanticVersion implements \Stringable, \JsonSerializable
{
    private function __construct(
        public int $major,
        public int $minor,
        public int $patch,
        public ?string $preRelease = null,
    ) {
    }

    public static function parse(string $version): self
    {
        $pattern = '/^v?(\d+)\.(\d+)(?:\.(\d+))?(?:-([a-zA-Z0-9.]+))?$/';

        if (!\preg_match($pattern, \trim($version), $matches)) {
            throw new \InvalidArgumentException(\sprintf('Invalid semantic version: "%s"', $version));
        }

        return new self(
            major: (int) $matches[1],
            minor: (int) $matches[2],
            patch: isset($matches[3]) && $matches[3] !== '' ? (int) $matches[3] : 0,
            preRelease: isset($matches[4]) && $matches[4] !== '' ? $matches[4] : null,
        );
    }

    public function isNewerThan(self $other): bool
    {
        return match (true) {
            $this->major !== $other->major => $this->major > $other->major,
            $this->minor !== $other->minor => $this->minor > $other->minor,
            $this->patch !== $other->patch => $this->patch > $other->patch,
            default => $this->comparePreRelease($other) > 0,
        };
    }

    public function isCompatibleWith(self $other): bool
    {
        return $this->major === $other->major;
    }

    public function getMajorGap(self $other): int
    {
        return \abs($this->major - $other->major);
    }

    public function getMinorGap(self $other): int
    {
        return \abs($this->minor - $other->minor);
    }

    public function getPatchGap(self $other): int
    {
        return \abs($this->patch - $other->patch);
    }

    public function isPreRelease(): bool
    {
        return $this->preRelease !== null;
    }

    public function equals(self $other): bool
    {
        return $this->major === $other->major
            && $this->minor === $other->minor
            && $this->patch === $other->patch
            && $this->preRelease === $other->preRelease;
    }

    public function __toString(): string
    {
        $version = \sprintf('%d.%d.%d', $this->major, $this->minor, $this->patch);

        if ($this->preRelease !== null) {
            $version .= '-' . $this->preRelease;
        }

        return $version;
    }

    public function jsonSerialize(): string
    {
        return (string) $this;
    }

    private function comparePreRelease(self $other): int
    {
        if ($this->preRelease === $other->preRelease) {
            return 0;
        }

        if ($this->preRelease === null) {
            return 1;
        }

        if ($other->preRelease === null) {
            return -1;
        }

        $aParts = \explode('.', $this->preRelease);
        $bParts = \explode('.', $other->preRelease);
        $length = \max(\count($aParts), \count($bParts));

        for ($i = 0; $i < $length; $i++) {
            $aPart = $aParts[$i] ?? '';
            $bPart = $bParts[$i] ?? '';
            $aIsNum = \ctype_digit($aPart);
            $bIsNum = \ctype_digit($bPart);

            if ($aIsNum && $bIsNum) {
                $diff = (int) $aPart - (int) $bPart;
                if ($diff !== 0) {
                    return $diff;
                }
            } elseif ($aIsNum !== $bIsNum) {
                return $aIsNum ? -1 : 1;
            } else {
                $diff = \strcmp($aPart, $bPart);
                if ($diff !== 0) {
                    return $diff;
                }
            }
        }

        return 0;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Dependency/Domain/ValueObject/SemanticVersionTest.php`
Expected: 18 tests PASS

- [ ] **Step 5: Commit**

```bash
git add backend/src/Dependency/Domain/ValueObject/SemanticVersion.php backend/tests/Unit/Dependency/Domain/ValueObject/SemanticVersionTest.php
git commit -m "feat(dependency): add SemanticVersion value object with comparison and gap analysis"
```

---

### Task 3: CveId Value Object + RiskLevel enum

**Files:**
- Create: `backend/src/Dependency/Domain/ValueObject/CveId.php`
- Create: `backend/src/Dependency/Domain/ValueObject/RiskLevel.php`
- Test: `backend/tests/Unit/Dependency/Domain/ValueObject/CveIdTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Dependency\Domain\ValueObject\CveId;

describe('CveId', function () {
    it('parses valid CVE id', function () {
        $cve = CveId::fromString('CVE-2024-12345');

        expect($cve->getYear())->toBe(2024)
            ->and($cve->getSequence())->toBe(12345)
            ->and((string) $cve)->toBe('CVE-2024-12345');
    });

    it('accepts long sequence numbers', function () {
        $cve = CveId::fromString('CVE-2025-1234567');

        expect($cve->getSequence())->toBe(1234567);
    });

    it('throws on invalid format', function () {
        CveId::fromString('GHSA-1234-abcd');
    })->throws(\InvalidArgumentException::class);

    it('throws on too short sequence', function () {
        CveId::fromString('CVE-2024-123');
    })->throws(\InvalidArgumentException::class);

    it('throws on empty string', function () {
        CveId::fromString('');
    })->throws(\InvalidArgumentException::class);

    it('equals identical CVE', function () {
        $a = CveId::fromString('CVE-2024-12345');
        $b = CveId::fromString('CVE-2024-12345');

        expect($a->equals($b))->toBeTrue();
    });

    it('does not equal different CVE', function () {
        $a = CveId::fromString('CVE-2024-12345');
        $b = CveId::fromString('CVE-2024-99999');

        expect($a->equals($b))->toBeFalse();
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Dependency/Domain/ValueObject/CveIdTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Write the RiskLevel enum**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\ValueObject;

enum RiskLevel: string
{
    case Critical = 'critical';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';
    case None = 'none';

    public function isAbove(self $other): bool
    {
        return $this->weight() > $other->weight();
    }

    public function weight(): int
    {
        return match ($this) {
            self::Critical => 5,
            self::High => 4,
            self::Medium => 3,
            self::Low => 2,
            self::None => 0,
        };
    }
}
```

- [ ] **Step 4: Write the CveId implementation**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\ValueObject;

final readonly class CveId implements \Stringable, \JsonSerializable
{
    private function __construct(
        private int $year,
        private int $sequence,
    ) {
    }

    public static function fromString(string $value): self
    {
        if (!\preg_match('/^CVE-(\d{4})-(\d{4,})$/', \trim($value), $matches)) {
            throw new \InvalidArgumentException(\sprintf('Invalid CVE identifier: "%s"', $value));
        }

        return new self(
            year: (int) $matches[1],
            sequence: (int) $matches[2],
        );
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    public function equals(self $other): bool
    {
        return $this->year === $other->year && $this->sequence === $other->sequence;
    }

    public function __toString(): string
    {
        return \sprintf('CVE-%d-%d', $this->year, $this->sequence);
    }

    public function jsonSerialize(): string
    {
        return (string) $this;
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Dependency/Domain/ValueObject/CveIdTest.php`
Expected: 7 tests PASS

- [ ] **Step 6: Commit**

```bash
git add backend/src/Dependency/Domain/ValueObject/CveId.php backend/src/Dependency/Domain/ValueObject/RiskLevel.php backend/tests/Unit/Dependency/Domain/ValueObject/CveIdTest.php
git commit -m "feat(dependency): add CveId value object and RiskLevel enum"
```

---

### Task 4: VulnerabilityScore Value Object

**Files:**
- Create: `backend/src/Dependency/Domain/ValueObject/VulnerabilityScore.php`
- Test: `backend/tests/Unit/Dependency/Domain/ValueObject/VulnerabilityScoreTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Dependency\Domain\Model\Severity;
use App\Dependency\Domain\ValueObject\RiskLevel;
use App\Dependency\Domain\ValueObject\VulnerabilityScore;

describe('VulnerabilityScore', function () {
    it('scores critical severity as high', function () {
        $score = VulnerabilityScore::fromSeverity(Severity::Critical, hasPatch: false);

        expect($score->toFloat())->toBeGreaterThanOrEqual(9.0)
            ->and($score->getRiskLevel())->toBe(RiskLevel::Critical);
    });

    it('scores low severity as low', function () {
        $score = VulnerabilityScore::fromSeverity(Severity::Low, hasPatch: true);

        expect($score->toFloat())->toBeLessThan(4.0)
            ->and($score->getRiskLevel())->toBe(RiskLevel::Low);
    });

    it('reduces score when patch is available', function () {
        $withoutPatch = VulnerabilityScore::fromSeverity(Severity::High, hasPatch: false);
        $withPatch = VulnerabilityScore::fromSeverity(Severity::High, hasPatch: true);

        expect($withPatch->toFloat())->toBeLessThan($withoutPatch->toFloat());
    });

    it('checks threshold correctly', function () {
        $score = VulnerabilityScore::fromSeverity(Severity::Critical, hasPatch: false);

        expect($score->isAboveThreshold(7.0))->toBeTrue()
            ->and($score->isAboveThreshold(10.0))->toBeFalse();
    });

    it('maps risk levels based on score ranges', function () {
        expect(VulnerabilityScore::fromSeverity(Severity::Critical, hasPatch: false)->getRiskLevel())->toBe(RiskLevel::Critical)
            ->and(VulnerabilityScore::fromSeverity(Severity::High, hasPatch: false)->getRiskLevel())->toBe(RiskLevel::High)
            ->and(VulnerabilityScore::fromSeverity(Severity::Medium, hasPatch: false)->getRiskLevel())->toBe(RiskLevel::Medium)
            ->and(VulnerabilityScore::fromSeverity(Severity::Low, hasPatch: true)->getRiskLevel())->toBe(RiskLevel::Low);
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Dependency/Domain/ValueObject/VulnerabilityScoreTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Write the implementation**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\ValueObject;

use App\Dependency\Domain\Model\Severity;

final readonly class VulnerabilityScore implements \JsonSerializable
{
    private function __construct(
        private float $score,
    ) {
    }

    public static function fromSeverity(Severity $severity, bool $hasPatch): self
    {
        $baseScore = match ($severity) {
            Severity::Critical => 9.5,
            Severity::High => 7.5,
            Severity::Medium => 5.0,
            Severity::Low => 2.5,
        };

        $patchReduction = $hasPatch ? $baseScore * 0.3 : 0.0;

        return new self(\round(\max(0.0, \min(10.0, $baseScore - $patchReduction)), 1));
    }

    public static function zero(): self
    {
        return new self(0.0);
    }

    public function toFloat(): float
    {
        return $this->score;
    }

    public function isAboveThreshold(float $threshold): bool
    {
        return $this->score >= $threshold;
    }

    public function getRiskLevel(): RiskLevel
    {
        return match (true) {
            $this->score >= 9.0 => RiskLevel::Critical,
            $this->score >= 7.0 => RiskLevel::High,
            $this->score >= 4.0 => RiskLevel::Medium,
            $this->score > 0.0 => RiskLevel::Low,
            default => RiskLevel::None,
        };
    }

    public function jsonSerialize(): float
    {
        return $this->score;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Dependency/Domain/ValueObject/VulnerabilityScoreTest.php`
Expected: 5 tests PASS

- [ ] **Step 5: Commit**

```bash
git add backend/src/Dependency/Domain/ValueObject/VulnerabilityScore.php backend/tests/Unit/Dependency/Domain/ValueObject/VulnerabilityScoreTest.php
git commit -m "feat(dependency): add VulnerabilityScore value object with CVSS-like scoring"
```

---

### Task 5: DependencyHealth Value Object

**Files:**
- Create: `backend/src/Dependency/Domain/ValueObject/DependencyHealth.php`
- Test: `backend/tests/Unit/Dependency/Domain/ValueObject/DependencyHealthTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Dependency\Domain\Model\Severity;
use App\Dependency\Domain\ValueObject\DependencyHealth;
use App\Dependency\Domain\ValueObject\RiskLevel;

describe('DependencyHealth', function () {
    it('scores perfectly healthy dependency at 100', function () {
        $health = DependencyHealth::calculate(
            majorGap: 0,
            minorGap: 0,
            patchGap: 0,
            vulnerabilitySeverities: [],
            isDeprecated: false,
            isNotFound: false,
        );

        expect($health->getScore())->toBe(100)
            ->and($health->isHealthy())->toBeTrue()
            ->and($health->getRiskLevel())->toBe(RiskLevel::None);
    });

    it('penalizes major version gap heavily', function () {
        $health = DependencyHealth::calculate(
            majorGap: 2,
            minorGap: 0,
            patchGap: 0,
            vulnerabilitySeverities: [],
            isDeprecated: false,
            isNotFound: false,
        );

        expect($health->getScore())->toBeLessThanOrEqual(20);
    });

    it('penalizes critical vulnerabilities', function () {
        $health = DependencyHealth::calculate(
            majorGap: 0,
            minorGap: 0,
            patchGap: 0,
            vulnerabilitySeverities: [Severity::Critical],
            isDeprecated: false,
            isNotFound: false,
        );

        expect($health->getScore())->toBeLessThanOrEqual(50)
            ->and($health->isHealthy())->toBeFalse();
    });

    it('accumulates multiple vulnerability penalties', function () {
        $single = DependencyHealth::calculate(
            majorGap: 0,
            minorGap: 0,
            patchGap: 0,
            vulnerabilitySeverities: [Severity::High],
            isDeprecated: false,
            isNotFound: false,
        );

        $multiple = DependencyHealth::calculate(
            majorGap: 0,
            minorGap: 0,
            patchGap: 0,
            vulnerabilitySeverities: [Severity::High, Severity::High, Severity::Medium],
            isDeprecated: false,
            isNotFound: false,
        );

        expect($multiple->getScore())->toBeLessThan($single->getScore());
    });

    it('penalizes deprecated status', function () {
        $health = DependencyHealth::calculate(
            majorGap: 0,
            minorGap: 0,
            patchGap: 0,
            vulnerabilitySeverities: [],
            isDeprecated: true,
            isNotFound: false,
        );

        expect($health->getScore())->toBeLessThanOrEqual(70)
            ->and($health->isHealthy())->toBeFalse();
    });

    it('penalizes not found status', function () {
        $health = DependencyHealth::calculate(
            majorGap: 0,
            minorGap: 0,
            patchGap: 0,
            vulnerabilitySeverities: [],
            isDeprecated: false,
            isNotFound: true,
        );

        expect($health->getScore())->toBeLessThanOrEqual(80);
    });

    it('never goes below 0', function () {
        $health = DependencyHealth::calculate(
            majorGap: 10,
            minorGap: 20,
            patchGap: 50,
            vulnerabilitySeverities: [Severity::Critical, Severity::Critical, Severity::Critical],
            isDeprecated: true,
            isNotFound: false,
        );

        expect($health->getScore())->toBeGreaterThanOrEqual(0);
    });

    it('maps risk level from score', function () {
        $critical = DependencyHealth::calculate(majorGap: 5, minorGap: 0, patchGap: 0, vulnerabilitySeverities: [Severity::Critical], isDeprecated: true, isNotFound: false);
        expect($critical->getRiskLevel())->toBe(RiskLevel::Critical);
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Dependency/Domain/ValueObject/DependencyHealthTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Write the implementation**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\ValueObject;

use App\Dependency\Domain\Model\Severity;

final readonly class DependencyHealth implements \JsonSerializable
{
    private const int WEIGHT_MAJOR_GAP = 40;
    private const int WEIGHT_MINOR_GAP = 20;
    private const int WEIGHT_PATCH_GAP = 5;
    private const int WEIGHT_DEPRECATED = 30;
    private const int WEIGHT_NOT_FOUND = 20;

    private function __construct(
        private int $score,
    ) {
    }

    /** @param list<Severity> $vulnerabilitySeverities */
    public static function calculate(
        int $majorGap,
        int $minorGap,
        int $patchGap,
        array $vulnerabilitySeverities,
        bool $isDeprecated,
        bool $isNotFound,
    ): self {
        $penalty = 0;
        $penalty += $majorGap * self::WEIGHT_MAJOR_GAP;
        $penalty += $minorGap * self::WEIGHT_MINOR_GAP;
        $penalty += $patchGap * self::WEIGHT_PATCH_GAP;

        foreach ($vulnerabilitySeverities as $severity) {
            $penalty += self::vulnerabilityWeight($severity);
        }

        if ($isDeprecated) {
            $penalty += self::WEIGHT_DEPRECATED;
        }

        if ($isNotFound) {
            $penalty += self::WEIGHT_NOT_FOUND;
        }

        return new self(\max(0, \min(100, 100 - $penalty)));
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function isHealthy(): bool
    {
        return $this->score >= 70;
    }

    public function getRiskLevel(): RiskLevel
    {
        return match (true) {
            $this->score <= 20 => RiskLevel::Critical,
            $this->score <= 40 => RiskLevel::High,
            $this->score <= 60 => RiskLevel::Medium,
            $this->score <= 80 => RiskLevel::Low,
            default => RiskLevel::None,
        };
    }

    public function jsonSerialize(): array
    {
        return [
            'score' => $this->score,
            'riskLevel' => $this->getRiskLevel()->value,
            'healthy' => $this->isHealthy(),
        ];
    }

    private static function vulnerabilityWeight(Severity $severity): int
    {
        return match ($severity) {
            Severity::Critical => 50,
            Severity::High => 30,
            Severity::Medium => 15,
            Severity::Low => 5,
        };
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Dependency/Domain/ValueObject/DependencyHealthTest.php`
Expected: 8 tests PASS

- [ ] **Step 5: Commit**

```bash
git add backend/src/Dependency/Domain/ValueObject/DependencyHealth.php backend/tests/Unit/Dependency/Domain/ValueObject/DependencyHealthTest.php
git commit -m "feat(dependency): add DependencyHealth value object with weighted scoring algorithm"
```

---

### Task 6: Version Comparison Strategies + Service

**Files:**
- Create: `backend/src/Dependency/Domain/Service/Strategy/VersionStrategyInterface.php`
- Create: `backend/src/Dependency/Domain/Service/Strategy/ComposerVersionStrategy.php`
- Create: `backend/src/Dependency/Domain/Service/Strategy/NpmVersionStrategy.php`
- Create: `backend/src/Dependency/Domain/Service/Strategy/PipVersionStrategy.php`
- Create: `backend/src/Dependency/Domain/Service/VersionComparisonService.php`
- Test: `backend/tests/Unit/Dependency/Domain/Service/VersionComparisonServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Dependency\Domain\Service\Strategy\ComposerVersionStrategy;
use App\Dependency\Domain\Service\Strategy\NpmVersionStrategy;
use App\Dependency\Domain\Service\Strategy\PipVersionStrategy;
use App\Dependency\Domain\Service\Strategy\VersionStrategyInterface;
use App\Dependency\Domain\Service\VersionComparisonService;
use App\Dependency\Domain\ValueObject\SemanticVersion;
use App\Shared\Domain\ValueObject\PackageManager;

describe('VersionStrategyInterface implementations', function () {
    describe('ComposerVersionStrategy', function () {
        it('detects outdated when major gap exists', function () {
            $strategy = new ComposerVersionStrategy();
            $current = SemanticVersion::parse('5.4.0');
            $latest = SemanticVersion::parse('7.2.0');

            expect($strategy->isOutdated($current, $latest))->toBeTrue();
        });

        it('is not outdated when on latest', function () {
            $strategy = new ComposerVersionStrategy();
            $current = SemanticVersion::parse('7.2.0');
            $latest = SemanticVersion::parse('7.2.0');

            expect($strategy->isOutdated($current, $latest))->toBeFalse();
        });

        it('tolerates patch-level differences', function () {
            $strategy = new ComposerVersionStrategy();
            $current = SemanticVersion::parse('7.2.0');
            $latest = SemanticVersion::parse('7.2.5');

            expect($strategy->isOutdated($current, $latest))->toBeFalse();
        });

        it('detects outdated on minor gap above threshold', function () {
            $strategy = new ComposerVersionStrategy();
            $current = SemanticVersion::parse('7.0.0');
            $latest = SemanticVersion::parse('7.4.0');

            expect($strategy->isOutdated($current, $latest))->toBeTrue();
        });

        it('supports composer package manager', function () {
            expect((new ComposerVersionStrategy())->supports(PackageManager::Composer))->toBeTrue()
                ->and((new ComposerVersionStrategy())->supports(PackageManager::Npm))->toBeFalse();
        });
    });

    describe('NpmVersionStrategy', function () {
        it('detects outdated on any major gap', function () {
            $strategy = new NpmVersionStrategy();
            $current = SemanticVersion::parse('17.0.0');
            $latest = SemanticVersion::parse('18.0.0');

            expect($strategy->isOutdated($current, $latest))->toBeTrue();
        });

        it('detects outdated on minor gap above threshold', function () {
            $strategy = new NpmVersionStrategy();
            $current = SemanticVersion::parse('18.0.0');
            $latest = SemanticVersion::parse('18.3.0');

            expect($strategy->isOutdated($current, $latest))->toBeTrue();
        });

        it('tolerates small minor differences', function () {
            $strategy = new NpmVersionStrategy();
            $current = SemanticVersion::parse('18.2.0');
            $latest = SemanticVersion::parse('18.3.0');

            expect($strategy->isOutdated($current, $latest))->toBeFalse();
        });

        it('supports npm package manager', function () {
            expect((new NpmVersionStrategy())->supports(PackageManager::Npm))->toBeTrue()
                ->and((new NpmVersionStrategy())->supports(PackageManager::Pip))->toBeFalse();
        });
    });

    describe('PipVersionStrategy', function () {
        it('detects outdated on major gap', function () {
            $strategy = new PipVersionStrategy();
            $current = SemanticVersion::parse('2.0.0');
            $latest = SemanticVersion::parse('3.0.0');

            expect($strategy->isOutdated($current, $latest))->toBeTrue();
        });

        it('supports pip package manager', function () {
            expect((new PipVersionStrategy())->supports(PackageManager::Pip))->toBeTrue();
        });
    });
});

describe('VersionComparisonService', function () {
    it('delegates to correct strategy for composer', function () {
        $service = new VersionComparisonService([
            new ComposerVersionStrategy(),
            new NpmVersionStrategy(),
            new PipVersionStrategy(),
        ]);

        $current = SemanticVersion::parse('5.4.0');
        $latest = SemanticVersion::parse('7.2.0');

        expect($service->isOutdated($current, $latest, PackageManager::Composer))->toBeTrue();
    });

    it('delegates to correct strategy for npm', function () {
        $service = new VersionComparisonService([
            new ComposerVersionStrategy(),
            new NpmVersionStrategy(),
            new PipVersionStrategy(),
        ]);

        $current = SemanticVersion::parse('18.2.0');
        $latest = SemanticVersion::parse('18.3.0');

        expect($service->isOutdated($current, $latest, PackageManager::Npm))->toBeFalse();
    });

    it('throws when no strategy found', function () {
        $service = new VersionComparisonService([]);

        $service->isOutdated(
            SemanticVersion::parse('1.0.0'),
            SemanticVersion::parse('2.0.0'),
            PackageManager::Composer,
        );
    })->throws(\RuntimeException::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Dependency/Domain/Service/VersionComparisonServiceTest.php`
Expected: FAIL — classes not found

- [ ] **Step 3: Write the VersionStrategyInterface**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Service\Strategy;

use App\Dependency\Domain\ValueObject\SemanticVersion;
use App\Shared\Domain\ValueObject\PackageManager;

interface VersionStrategyInterface
{
    public function supports(PackageManager $manager): bool;

    public function isOutdated(SemanticVersion $current, SemanticVersion $latest): bool;
}
```

- [ ] **Step 4: Write ComposerVersionStrategy**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Service\Strategy;

use App\Dependency\Domain\ValueObject\SemanticVersion;
use App\Shared\Domain\ValueObject\PackageManager;

final readonly class ComposerVersionStrategy implements VersionStrategyInterface
{
    private const int MINOR_GAP_THRESHOLD = 3;

    #[\Override]
    public function supports(PackageManager $manager): bool
    {
        return $manager === PackageManager::Composer;
    }

    #[\Override]
    public function isOutdated(SemanticVersion $current, SemanticVersion $latest): bool
    {
        if ($current->getMajorGap($latest) > 0 && $latest->isNewerThan($current)) {
            return true;
        }

        if ($current->major === $latest->major && $current->getMinorGap($latest) >= self::MINOR_GAP_THRESHOLD) {
            return true;
        }

        return false;
    }
}
```

- [ ] **Step 5: Write NpmVersionStrategy**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Service\Strategy;

use App\Dependency\Domain\ValueObject\SemanticVersion;
use App\Shared\Domain\ValueObject\PackageManager;

final readonly class NpmVersionStrategy implements VersionStrategyInterface
{
    private const int MINOR_GAP_THRESHOLD = 2;

    #[\Override]
    public function supports(PackageManager $manager): bool
    {
        return $manager === PackageManager::Npm;
    }

    #[\Override]
    public function isOutdated(SemanticVersion $current, SemanticVersion $latest): bool
    {
        if ($current->getMajorGap($latest) > 0 && $latest->isNewerThan($current)) {
            return true;
        }

        if ($current->major === $latest->major && $current->getMinorGap($latest) >= self::MINOR_GAP_THRESHOLD) {
            return true;
        }

        return false;
    }
}
```

- [ ] **Step 6: Write PipVersionStrategy**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Service\Strategy;

use App\Dependency\Domain\ValueObject\SemanticVersion;
use App\Shared\Domain\ValueObject\PackageManager;

final readonly class PipVersionStrategy implements VersionStrategyInterface
{
    private const int MINOR_GAP_THRESHOLD = 3;

    #[\Override]
    public function supports(PackageManager $manager): bool
    {
        return $manager === PackageManager::Pip;
    }

    #[\Override]
    public function isOutdated(SemanticVersion $current, SemanticVersion $latest): bool
    {
        if ($current->getMajorGap($latest) > 0 && $latest->isNewerThan($current)) {
            return true;
        }

        if ($current->major === $latest->major && $current->getMinorGap($latest) >= self::MINOR_GAP_THRESHOLD) {
            return true;
        }

        return false;
    }
}
```

- [ ] **Step 7: Write VersionComparisonService**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Service;

use App\Dependency\Domain\Service\Strategy\VersionStrategyInterface;
use App\Dependency\Domain\ValueObject\SemanticVersion;
use App\Shared\Domain\ValueObject\PackageManager;

final readonly class VersionComparisonService
{
    /** @param iterable<VersionStrategyInterface> $strategies */
    public function __construct(
        private iterable $strategies,
    ) {
    }

    public function isOutdated(SemanticVersion $current, SemanticVersion $latest, PackageManager $manager): bool
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($manager)) {
                return $strategy->isOutdated($current, $latest);
            }
        }

        throw new \RuntimeException(\sprintf('No version strategy found for package manager "%s"', $manager->value));
    }
}
```

- [ ] **Step 8: Run tests to verify they pass**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Dependency/Domain/Service/VersionComparisonServiceTest.php`
Expected: 12 tests PASS

- [ ] **Step 9: Commit**

```bash
git add backend/src/Dependency/Domain/Service/ backend/tests/Unit/Dependency/Domain/Service/VersionComparisonServiceTest.php
git commit -m "feat(dependency): add VersionComparisonService with Strategy pattern per PackageManager"
```

---

### Task 7: VulnerabilityAssessor with Chain of Responsibility

**Files:**
- Create: `backend/src/Dependency/Domain/ValueObject/RiskAssessment.php`
- Create: `backend/src/Dependency/Domain/Service/Assessment/AssessmentHandlerInterface.php`
- Create: `backend/src/Dependency/Domain/Service/Assessment/SeverityHandler.php`
- Create: `backend/src/Dependency/Domain/Service/Assessment/PatchAvailabilityHandler.php`
- Create: `backend/src/Dependency/Domain/Service/Assessment/AgeHandler.php`
- Create: `backend/src/Dependency/Domain/Service/VulnerabilityAssessor.php`
- Test: `backend/tests/Unit/Dependency/Domain/Service/VulnerabilityAssessorTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Dependency\Domain\Model\Severity;
use App\Dependency\Domain\Model\VulnerabilityStatus;
use App\Dependency\Domain\Service\Assessment\AgeHandler;
use App\Dependency\Domain\Service\Assessment\PatchAvailabilityHandler;
use App\Dependency\Domain\Service\Assessment\SeverityHandler;
use App\Dependency\Domain\Service\VulnerabilityAssessor;
use App\Dependency\Domain\ValueObject\RiskAssessment;
use App\Dependency\Domain\ValueObject\RiskLevel;

function createVulnData(
    Severity $severity = Severity::Medium,
    VulnerabilityStatus $status = VulnerabilityStatus::Open,
    bool $hasPatch = false,
    ?\DateTimeImmutable $detectedAt = null,
): array {
    return [
        'severity' => $severity,
        'status' => $status,
        'hasPatch' => $hasPatch,
        'detectedAt' => $detectedAt ?? new \DateTimeImmutable(),
    ];
}

describe('VulnerabilityAssessor', function () {
    it('assesses no vulnerabilities as no risk', function () {
        $assessor = new VulnerabilityAssessor([
            new SeverityHandler(),
            new PatchAvailabilityHandler(),
            new AgeHandler(),
        ]);

        $result = $assessor->assess([]);

        expect($result)->toBeInstanceOf(RiskAssessment::class)
            ->and($result->level)->toBe(RiskLevel::None)
            ->and($result->score)->toBe(0.0)
            ->and($result->recommendations)->toBeEmpty();
    });

    it('assesses critical unpatched vulnerability as critical risk', function () {
        $assessor = new VulnerabilityAssessor([
            new SeverityHandler(),
            new PatchAvailabilityHandler(),
            new AgeHandler(),
        ]);

        $result = $assessor->assess([
            \createVulnData(Severity::Critical, hasPatch: false),
        ]);

        expect($result->level)->toBe(RiskLevel::Critical)
            ->and($result->score)->toBeGreaterThanOrEqual(9.0)
            ->and($result->recommendations)->not->toBeEmpty();
    });

    it('reduces risk when patch is available', function () {
        $assessor = new VulnerabilityAssessor([
            new SeverityHandler(),
            new PatchAvailabilityHandler(),
            new AgeHandler(),
        ]);

        $unpatched = $assessor->assess([\createVulnData(Severity::High, hasPatch: false)]);
        $patched = $assessor->assess([\createVulnData(Severity::High, hasPatch: true)]);

        expect($patched->score)->toBeLessThan($unpatched->score);
    });

    it('increases risk for old unresolved vulnerabilities', function () {
        $assessor = new VulnerabilityAssessor([
            new SeverityHandler(),
            new PatchAvailabilityHandler(),
            new AgeHandler(),
        ]);

        $recent = $assessor->assess([
            \createVulnData(Severity::High, detectedAt: new \DateTimeImmutable('-1 day')),
        ]);
        $old = $assessor->assess([
            \createVulnData(Severity::High, detectedAt: new \DateTimeImmutable('-90 days')),
        ]);

        expect($old->score)->toBeGreaterThan($recent->score);
    });

    it('accumulates risk from multiple vulnerabilities', function () {
        $assessor = new VulnerabilityAssessor([
            new SeverityHandler(),
            new PatchAvailabilityHandler(),
            new AgeHandler(),
        ]);

        $single = $assessor->assess([\createVulnData(Severity::Medium)]);
        $multiple = $assessor->assess([
            \createVulnData(Severity::Medium),
            \createVulnData(Severity::Medium),
            \createVulnData(Severity::Low),
        ]);

        expect($multiple->score)->toBeGreaterThan($single->score);
    });

    it('skips fixed vulnerabilities', function () {
        $assessor = new VulnerabilityAssessor([
            new SeverityHandler(),
            new PatchAvailabilityHandler(),
            new AgeHandler(),
        ]);

        $result = $assessor->assess([
            \createVulnData(Severity::Critical, status: VulnerabilityStatus::Fixed),
        ]);

        expect($result->level)->toBe(RiskLevel::None)
            ->and($result->score)->toBe(0.0);
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Dependency/Domain/Service/VulnerabilityAssessorTest.php`
Expected: FAIL — classes not found

- [ ] **Step 3: Write RiskAssessment VO**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\ValueObject;

final readonly class RiskAssessment implements \JsonSerializable
{
    /** @param list<string> $recommendations */
    public function __construct(
        public RiskLevel $level,
        public float $score,
        public array $recommendations,
    ) {
    }

    public static function none(): self
    {
        return new self(RiskLevel::None, 0.0, []);
    }

    public function jsonSerialize(): array
    {
        return [
            'level' => $this->level->value,
            'score' => $this->score,
            'recommendations' => $this->recommendations,
        ];
    }
}
```

- [ ] **Step 4: Write AssessmentHandlerInterface**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Service\Assessment;

interface AssessmentHandlerInterface
{
    /**
     * @param list<array{severity: \App\Dependency\Domain\Model\Severity, status: \App\Dependency\Domain\Model\VulnerabilityStatus, hasPatch: bool, detectedAt: \DateTimeImmutable}> $vulnerabilities
     * @return array{score: float, recommendations: list<string>}
     */
    public function assess(array $vulnerabilities): array;
}
```

- [ ] **Step 5: Write SeverityHandler**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Service\Assessment;

use App\Dependency\Domain\Model\Severity;
use App\Dependency\Domain\Model\VulnerabilityStatus;

final readonly class SeverityHandler implements AssessmentHandlerInterface
{
    #[\Override]
    public function assess(array $vulnerabilities): array
    {
        $score = 0.0;
        $recommendations = [];

        foreach ($vulnerabilities as $vuln) {
            if ($vuln['status'] === VulnerabilityStatus::Fixed) {
                continue;
            }

            $score += match ($vuln['severity']) {
                Severity::Critical => 4.0,
                Severity::High => 2.5,
                Severity::Medium => 1.5,
                Severity::Low => 0.5,
            };
        }

        $criticalCount = \count(\array_filter(
            $vulnerabilities,
            static fn (array $v) => $v['severity'] === Severity::Critical && $v['status'] !== VulnerabilityStatus::Fixed,
        ));

        if ($criticalCount > 0) {
            $recommendations[] = \sprintf('Resolve %d critical vulnerabilit%s immediately', $criticalCount, $criticalCount > 1 ? 'ies' : 'y');
        }

        return ['score' => $score, 'recommendations' => $recommendations];
    }
}
```

- [ ] **Step 6: Write PatchAvailabilityHandler**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Service\Assessment;

use App\Dependency\Domain\Model\VulnerabilityStatus;

final readonly class PatchAvailabilityHandler implements AssessmentHandlerInterface
{
    #[\Override]
    public function assess(array $vulnerabilities): array
    {
        $score = 0.0;
        $recommendations = [];
        $unpatchedCount = 0;

        foreach ($vulnerabilities as $vuln) {
            if ($vuln['status'] === VulnerabilityStatus::Fixed) {
                continue;
            }

            if ($vuln['hasPatch']) {
                $score -= 0.5;
            } else {
                $score += 1.0;
                $unpatchedCount++;
            }
        }

        if ($unpatchedCount > 0) {
            $recommendations[] = \sprintf('%d vulnerabilit%s ha%s no available patch — consider alternatives or workarounds', $unpatchedCount, $unpatchedCount > 1 ? 'ies' : 'y', $unpatchedCount > 1 ? 've' : 's');
        }

        return ['score' => \max(0.0, $score), 'recommendations' => $recommendations];
    }
}
```

- [ ] **Step 7: Write AgeHandler**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Service\Assessment;

use App\Dependency\Domain\Model\VulnerabilityStatus;

final readonly class AgeHandler implements AssessmentHandlerInterface
{
    private const int STALE_DAYS_THRESHOLD = 30;

    #[\Override]
    public function assess(array $vulnerabilities): array
    {
        $score = 0.0;
        $recommendations = [];
        $now = new \DateTimeImmutable();
        $staleCount = 0;

        foreach ($vulnerabilities as $vuln) {
            if ($vuln['status'] === VulnerabilityStatus::Fixed) {
                continue;
            }

            $daysSinceDetection = (int) $now->diff($vuln['detectedAt'])->days;

            if ($daysSinceDetection > self::STALE_DAYS_THRESHOLD) {
                $score += \min(2.0, $daysSinceDetection / 30.0 * 0.5);
                $staleCount++;
            }
        }

        if ($staleCount > 0) {
            $recommendations[] = \sprintf('%d vulnerabilit%s unresolved for over %d days', $staleCount, $staleCount > 1 ? 'ies' : 'y', self::STALE_DAYS_THRESHOLD);
        }

        return ['score' => $score, 'recommendations' => $recommendations];
    }
}
```

- [ ] **Step 8: Write VulnerabilityAssessor**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Service;

use App\Dependency\Domain\Service\Assessment\AssessmentHandlerInterface;
use App\Dependency\Domain\ValueObject\RiskAssessment;
use App\Dependency\Domain\ValueObject\RiskLevel;

final readonly class VulnerabilityAssessor
{
    /** @param iterable<AssessmentHandlerInterface> $handlers */
    public function __construct(
        private iterable $handlers,
    ) {
    }

    /** @param list<array{severity: \App\Dependency\Domain\Model\Severity, status: \App\Dependency\Domain\Model\VulnerabilityStatus, hasPatch: bool, detectedAt: \DateTimeImmutable}> $vulnerabilities */
    public function assess(array $vulnerabilities): RiskAssessment
    {
        if ($vulnerabilities === []) {
            return RiskAssessment::none();
        }

        $totalScore = 0.0;
        $allRecommendations = [];

        foreach ($this->handlers as $handler) {
            $result = $handler->assess($vulnerabilities);
            $totalScore += $result['score'];
            $allRecommendations = [...$allRecommendations, ...$result['recommendations']];
        }

        $normalizedScore = \round(\min(10.0, \max(0.0, $totalScore)), 1);

        $level = match (true) {
            $normalizedScore >= 9.0 => RiskLevel::Critical,
            $normalizedScore >= 7.0 => RiskLevel::High,
            $normalizedScore >= 4.0 => RiskLevel::Medium,
            $normalizedScore > 0.0 => RiskLevel::Low,
            default => RiskLevel::None,
        };

        return new RiskAssessment($level, $normalizedScore, $allRecommendations);
    }
}
```

- [ ] **Step 9: Run tests to verify they pass**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Dependency/Domain/Service/VulnerabilityAssessorTest.php`
Expected: 6 tests PASS

- [ ] **Step 10: Commit**

```bash
git add backend/src/Dependency/Domain/ValueObject/RiskAssessment.php backend/src/Dependency/Domain/Service/Assessment/ backend/src/Dependency/Domain/Service/VulnerabilityAssessor.php backend/tests/Unit/Dependency/Domain/Service/VulnerabilityAssessorTest.php
git commit -m "feat(dependency): add VulnerabilityAssessor with Chain of Responsibility pattern"
```

---

### Task 8: DependencyHealthCalculator

**Files:**
- Create: `backend/src/Dependency/Domain/Service/DependencyHealthCalculator.php`
- Test: `backend/tests/Unit/Dependency/Domain/Service/DependencyHealthCalculatorTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\RegistryStatus;
use App\Dependency\Domain\Model\Severity;
use App\Dependency\Domain\Model\Vulnerability;
use App\Dependency\Domain\Model\VulnerabilityStatus;
use App\Dependency\Domain\Service\DependencyHealthCalculator;
use App\Dependency\Domain\Service\Strategy\ComposerVersionStrategy;
use App\Dependency\Domain\Service\Strategy\NpmVersionStrategy;
use App\Dependency\Domain\Service\Strategy\PipVersionStrategy;
use App\Dependency\Domain\Service\VersionComparisonService;
use App\Dependency\Domain\ValueObject\DependencyHealth;
use App\Dependency\Domain\ValueObject\RiskLevel;
use App\Dependency\Domain\ValueObject\SemanticVersion;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use Symfony\Component\Uid\Uuid;

function createTestDependency(
    string $currentVersion = '1.0.0',
    string $latestVersion = '1.0.0',
    PackageManager $manager = PackageManager::Composer,
    RegistryStatus $registryStatus = RegistryStatus::Synced,
): Dependency {
    $dep = Dependency::create(
        name: 'test/package',
        currentVersion: $currentVersion,
        latestVersion: $latestVersion,
        ltsVersion: $latestVersion,
        packageManager: $manager,
        type: DependencyType::Runtime,
        isOutdated: false,
        projectId: Uuid::v4(),
    );
    $dep->markRegistryStatus($registryStatus);

    return $dep;
}

function addVulnerability(Dependency $dep, Severity $severity = Severity::Medium): void
{
    Vulnerability::create(
        cveId: \sprintf('CVE-2024-%05d', \random_int(10000, 99999)),
        severity: $severity,
        title: 'Test vulnerability',
        description: 'Test description',
        patchedVersion: '999.0.0',
        status: VulnerabilityStatus::Open,
        detectedAt: new \DateTimeImmutable(),
        dependency: $dep,
    );
}

describe('DependencyHealthCalculator', function () {
    it('calculates healthy score for up-to-date dependency', function () {
        $calculator = new DependencyHealthCalculator();
        $dep = \createTestDependency('1.0.0', '1.0.0');

        $health = $calculator->calculate($dep);

        expect($health)->toBeInstanceOf(DependencyHealth::class)
            ->and($health->getScore())->toBe(100)
            ->and($health->isHealthy())->toBeTrue();
    });

    it('penalizes major version gap', function () {
        $calculator = new DependencyHealthCalculator();
        $dep = \createTestDependency('1.0.0', '3.0.0');

        $health = $calculator->calculate($dep);

        expect($health->getScore())->toBeLessThanOrEqual(20)
            ->and($health->isHealthy())->toBeFalse();
    });

    it('penalizes vulnerabilities', function () {
        $calculator = new DependencyHealthCalculator();
        $dep = \createTestDependency('1.0.0', '1.0.0');
        \addVulnerability($dep, Severity::Critical);

        $health = $calculator->calculate($dep);

        expect($health->getScore())->toBeLessThanOrEqual(50)
            ->and($health->isHealthy())->toBeFalse();
    });

    it('penalizes deprecated status', function () {
        $calculator = new DependencyHealthCalculator();
        $dep = \createTestDependency('1.0.0', '1.0.0', registryStatus: RegistryStatus::Deprecated);

        $health = $calculator->calculate($dep);

        expect($health->getScore())->toBeLessThanOrEqual(70);
    });

    it('handles non-parseable versions gracefully', function () {
        $calculator = new DependencyHealthCalculator();
        $dep = \createTestDependency('dev-main', '1.0.0');

        $health = $calculator->calculate($dep);

        expect($health->getScore())->toBeGreaterThanOrEqual(0)
            ->and($health->getScore())->toBeLessThanOrEqual(100);
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Dependency/Domain/Service/DependencyHealthCalculatorTest.php`
Expected: FAIL — class not found

- [ ] **Step 3: Write the implementation**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Service;

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\RegistryStatus;
use App\Dependency\Domain\ValueObject\DependencyHealth;
use App\Dependency\Domain\ValueObject\SemanticVersion;

final readonly class DependencyHealthCalculator
{
    public function calculate(Dependency $dependency): DependencyHealth
    {
        $majorGap = 0;
        $minorGap = 0;
        $patchGap = 0;

        try {
            $current = SemanticVersion::parse($dependency->getCurrentVersion());
            $latest = SemanticVersion::parse($dependency->getLatestVersion());
            $majorGap = $current->getMajorGap($latest);
            $minorGap = $current->getMinorGap($latest);
            $patchGap = $current->getPatchGap($latest);
        } catch (\InvalidArgumentException) {
        }

        $severities = [];
        foreach ($dependency->getVulnerabilities() as $vuln) {
            $severities[] = $vuln->getSeverity();
        }

        return DependencyHealth::calculate(
            majorGap: $majorGap,
            minorGap: $minorGap,
            patchGap: $patchGap,
            vulnerabilitySeverities: $severities,
            isDeprecated: $dependency->getRegistryStatus() === RegistryStatus::Deprecated,
            isNotFound: $dependency->getRegistryStatus() === RegistryStatus::NotFound,
        );
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Dependency/Domain/Service/DependencyHealthCalculatorTest.php`
Expected: 5 tests PASS

- [ ] **Step 5: Commit**

```bash
git add backend/src/Dependency/Domain/Service/DependencyHealthCalculator.php backend/tests/Unit/Dependency/Domain/Service/DependencyHealthCalculatorTest.php
git commit -m "feat(dependency): add DependencyHealthCalculator domain service"
```

---

### Task 9: Enriched Domain Events

**Files:**
- Create: `backend/src/Dependency/Domain/Event/DependencyUpgraded.php`
- Create: `backend/src/Dependency/Domain/Event/VulnerabilityDetected.php`
- Create: `backend/src/Dependency/Domain/Event/VulnerabilityResolved.php`
- Create: `backend/src/Dependency/Domain/Event/DependencyHealthChanged.php`
- Modify: `backend/src/Dependency/Domain/Event/DependencyCreated.php`
- Modify: `backend/src/Dependency/Domain/Event/DependencyDeleted.php`

- [ ] **Step 1: Create DependencyUpgraded event**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Event;

final readonly class DependencyUpgraded
{
    public function __construct(
        public string $dependencyId,
        public string $name,
        public string $previousVersion,
        public string $newVersion,
        public string $gapType,
    ) {
    }
}
```

- [ ] **Step 2: Create VulnerabilityDetected event**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Event;

final readonly class VulnerabilityDetected
{
    public function __construct(
        public string $dependencyId,
        public string $dependencyName,
        public string $cveId,
        public string $severity,
        public string $affectedVersion,
    ) {
    }
}
```

- [ ] **Step 3: Create VulnerabilityResolved event**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Event;

final readonly class VulnerabilityResolved
{
    public function __construct(
        public string $dependencyId,
        public string $cveId,
        public string $patchedVersion,
    ) {
    }
}
```

- [ ] **Step 4: Create DependencyHealthChanged event**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Event;

final readonly class DependencyHealthChanged
{
    public function __construct(
        public string $dependencyId,
        public string $name,
        public int $previousScore,
        public int $newScore,
        public string $riskLevel,
    ) {
    }
}
```

- [ ] **Step 5: Enrich DependencyCreated with context**

Modify `backend/src/Dependency/Domain/Event/DependencyCreated.php` — add `packageManager` and `currentVersion`:

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Event;

final readonly class DependencyCreated
{
    public function __construct(
        public string $dependencyId,
        public string $name,
        public string $packageManager,
        public string $currentVersion,
        public string $projectId,
    ) {
    }
}
```

- [ ] **Step 6: Enrich DependencyDeleted with context**

Modify `backend/src/Dependency/Domain/Event/DependencyDeleted.php` — add `packageManager`:

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Event;

final readonly class DependencyDeleted
{
    public function __construct(
        public string $dependencyId,
        public string $name,
        public string $packageManager,
    ) {
    }
}
```

- [ ] **Step 7: Commit**

```bash
git add backend/src/Dependency/Domain/Event/
git commit -m "feat(dependency): add rich domain events with contextual data"
```

---

### Task 10: Enriched Dependency Aggregate Root

**Files:**
- Modify: `backend/src/Dependency/Domain/Model/Dependency.php`
- Test: `backend/tests/Unit/Dependency/Domain/Model/DependencyAggregateTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Dependency\Domain\Event\DependencyCreated;
use App\Dependency\Domain\Event\DependencyHealthChanged;
use App\Dependency\Domain\Event\DependencyUpgraded;
use App\Dependency\Domain\Event\VulnerabilityDetected;
use App\Dependency\Domain\Event\VulnerabilityResolved;
use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\RegistryStatus;
use App\Dependency\Domain\Model\Severity;
use App\Dependency\Domain\Model\VulnerabilityStatus;
use App\Dependency\Domain\Service\DependencyHealthCalculator;
use App\Dependency\Domain\ValueObject\CveId;
use App\Dependency\Domain\ValueObject\SemanticVersion;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use Symfony\Component\Uid\Uuid;

function createAggregateDependency(string $currentVersion = '1.0.0', string $latestVersion = '1.0.0'): Dependency
{
    return Dependency::create(
        name: 'symfony/console',
        currentVersion: $currentVersion,
        latestVersion: $latestVersion,
        ltsVersion: $latestVersion,
        packageManager: PackageManager::Composer,
        type: DependencyType::Runtime,
        isOutdated: false,
        projectId: Uuid::v4(),
    );
}

describe('Dependency Aggregate', function () {
    describe('create', function () {
        it('records DependencyCreated event', function () {
            $dep = \createAggregateDependency();
            $events = $dep->pullDomainEvents();

            expect($events)->toHaveCount(1)
                ->and($events[0])->toBeInstanceOf(DependencyCreated::class)
                ->and($events[0]->name)->toBe('symfony/console')
                ->and($events[0]->packageManager)->toBe('composer');
        });
    });

    describe('upgrade', function () {
        it('updates version and records event', function () {
            $dep = \createAggregateDependency('1.0.0', '2.0.0');
            $dep->pullDomainEvents();

            $dep->upgrade(SemanticVersion::parse('2.0.0'));

            expect($dep->getCurrentVersion())->toBe('2.0.0');
            $events = $dep->pullDomainEvents();
            expect($events)->toHaveCount(1)
                ->and($events[0])->toBeInstanceOf(DependencyUpgraded::class)
                ->and($events[0]->previousVersion)->toBe('1.0.0')
                ->and($events[0]->newVersion)->toBe('2.0.0')
                ->and($events[0]->gapType)->toBe('major');
        });

        it('detects minor gap type', function () {
            $dep = \createAggregateDependency('1.0.0', '1.3.0');
            $dep->pullDomainEvents();

            $dep->upgrade(SemanticVersion::parse('1.3.0'));

            $events = $dep->pullDomainEvents();
            expect($events[0]->gapType)->toBe('minor');
        });

        it('does nothing when version is same', function () {
            $dep = \createAggregateDependency('1.0.0', '1.0.0');
            $dep->pullDomainEvents();

            $dep->upgrade(SemanticVersion::parse('1.0.0'));

            expect($dep->pullDomainEvents())->toBeEmpty();
        });
    });

    describe('reportVulnerability', function () {
        it('adds vulnerability and records event', function () {
            $dep = \createAggregateDependency();
            $dep->pullDomainEvents();

            $dep->reportVulnerability(
                cveId: 'CVE-2024-12345',
                severity: Severity::High,
                title: 'Test vuln',
                description: 'A test vulnerability',
                patchedVersion: '1.0.1',
            );

            expect($dep->getVulnerabilityCount())->toBe(1);
            $events = $dep->pullDomainEvents();
            expect($events)->toHaveCount(1)
                ->and($events[0])->toBeInstanceOf(VulnerabilityDetected::class)
                ->and($events[0]->cveId)->toBe('CVE-2024-12345')
                ->and($events[0]->severity)->toBe('high');
        });

        it('rejects duplicate CVE', function () {
            $dep = \createAggregateDependency();

            $dep->reportVulnerability(
                cveId: 'CVE-2024-12345',
                severity: Severity::High,
                title: 'First',
                description: 'First description',
                patchedVersion: '1.0.1',
            );

            $dep->reportVulnerability(
                cveId: 'CVE-2024-12345',
                severity: Severity::Critical,
                title: 'Duplicate',
                description: 'Duplicate description',
                patchedVersion: '1.0.2',
            );

            expect($dep->getVulnerabilityCount())->toBe(1);
        });
    });

    describe('resolveVulnerability', function () {
        it('marks vulnerability as fixed and records event', function () {
            $dep = \createAggregateDependency();
            $dep->reportVulnerability(
                cveId: 'CVE-2024-12345',
                severity: Severity::High,
                title: 'Test vuln',
                description: 'Desc',
                patchedVersion: '1.0.1',
            );
            $dep->pullDomainEvents();

            $dep->resolveVulnerability(CveId::fromString('CVE-2024-12345'), '1.0.1');

            $events = $dep->pullDomainEvents();
            expect($events)->toHaveCount(1)
                ->and($events[0])->toBeInstanceOf(VulnerabilityResolved::class)
                ->and($events[0]->cveId)->toBe('CVE-2024-12345')
                ->and($events[0]->patchedVersion)->toBe('1.0.1');
        });
    });

    describe('markDeprecated', function () {
        it('transitions to deprecated status', function () {
            $dep = \createAggregateDependency();

            $dep->markDeprecated();

            expect($dep->getRegistryStatus())->toBe(RegistryStatus::Deprecated);
        });
    });

    describe('markSynced', function () {
        it('transitions to synced status', function () {
            $dep = \createAggregateDependency();

            $dep->markSynced();

            expect($dep->getRegistryStatus())->toBe(RegistryStatus::Synced);
        });
    });

    describe('getSemanticCurrentVersion', function () {
        it('returns parsed SemanticVersion', function () {
            $dep = \createAggregateDependency('2.3.1');

            $sv = $dep->getSemanticCurrentVersion();

            expect($sv)->toBeInstanceOf(SemanticVersion::class)
                ->and($sv->major)->toBe(2)
                ->and($sv->minor)->toBe(3);
        });

        it('returns null for unparseable version', function () {
            $dep = \createAggregateDependency('dev-main');

            expect($dep->getSemanticCurrentVersion())->toBeNull();
        });
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Dependency/Domain/Model/DependencyAggregateTest.php`
Expected: FAIL — methods not found on Dependency

- [ ] **Step 3: Modify Dependency entity**

Add `use RecordsDomainEvents` trait and new business methods to `backend/src/Dependency/Domain/Model/Dependency.php`.

Add at top of class:
```php
use App\Shared\Domain\Model\RecordsDomainEvents;
```

Add the trait:
```php
use RecordsDomainEvents;
```

Modify `create()` factory to record event:
```php
public static function create(
    string $name,
    string $currentVersion,
    string $latestVersion,
    string $ltsVersion,
    PackageManager $packageManager,
    DependencyType $type,
    bool $isOutdated,
    Uuid $projectId,
    ?string $repositoryUrl = null,
): self {
    if (\trim($name) === '' || \trim($currentVersion) === '') {
        throw new \InvalidArgumentException('Dependency name and currentVersion must not be empty');
    }

    $dependency = new self();
    $dependency->id = Uuid::v4();
    $dependency->name = $name;
    $dependency->currentVersion = $currentVersion;
    $dependency->latestVersion = $latestVersion;
    $dependency->ltsVersion = $ltsVersion;
    $dependency->packageManager = $packageManager;
    $dependency->type = $type;
    $dependency->isOutdated = $isOutdated;
    $dependency->projectId = $projectId;
    $dependency->repositoryUrl = $repositoryUrl;
    $dependency->registryStatus = RegistryStatus::Pending;
    $dependency->createdAt = new \DateTimeImmutable();
    $dependency->updatedAt = new \DateTimeImmutable();
    $dependency->vulnerabilities = new ArrayCollection();

    $dependency->recordEvent(new DependencyCreated(
        dependencyId: $dependency->id->toRfc4122(),
        name: $name,
        packageManager: $packageManager->value,
        currentVersion: $currentVersion,
        projectId: $projectId->toRfc4122(),
    ));

    return $dependency;
}
```

Add new methods:
```php
public function upgrade(SemanticVersion $newVersion): void
{
    $newVersionString = (string) $newVersion;

    if ($this->currentVersion === $newVersionString) {
        return;
    }

    $previousVersion = $this->currentVersion;
    $this->currentVersion = $newVersionString;
    $this->isOutdated = $newVersionString !== $this->latestVersion;
    $this->updatedAt = new \DateTimeImmutable();

    $gapType = $this->determineGapType($previousVersion, $newVersionString);

    $this->recordEvent(new DependencyUpgraded(
        dependencyId: $this->id->toRfc4122(),
        name: $this->name,
        previousVersion: $previousVersion,
        newVersion: $newVersionString,
        gapType: $gapType,
    ));
}

public function reportVulnerability(
    string $cveId,
    Severity $severity,
    string $title,
    string $description,
    string $patchedVersion,
): void {
    foreach ($this->vulnerabilities as $existing) {
        if ($existing->getCveId() === $cveId) {
            return;
        }
    }

    $vuln = Vulnerability::create(
        cveId: $cveId,
        severity: $severity,
        title: $title,
        description: $description,
        patchedVersion: $patchedVersion,
        status: VulnerabilityStatus::Open,
        detectedAt: new \DateTimeImmutable(),
        dependency: $this,
    );

    $this->vulnerabilities->add($vuln);

    $this->recordEvent(new VulnerabilityDetected(
        dependencyId: $this->id->toRfc4122(),
        dependencyName: $this->name,
        cveId: $cveId,
        severity: $severity->value,
        affectedVersion: $this->currentVersion,
    ));
}

public function resolveVulnerability(CveId $cveId, string $patchedVersion): void
{
    foreach ($this->vulnerabilities as $vuln) {
        if ($vuln->getCveId() === (string) $cveId) {
            $vuln->update(status: VulnerabilityStatus::Fixed);

            $this->recordEvent(new VulnerabilityResolved(
                dependencyId: $this->id->toRfc4122(),
                cveId: (string) $cveId,
                patchedVersion: $patchedVersion,
            ));

            return;
        }
    }
}

public function markDeprecated(): void
{
    $this->registryStatus = RegistryStatus::Deprecated;
    $this->updatedAt = new \DateTimeImmutable();
}

public function markSynced(): void
{
    $this->registryStatus = RegistryStatus::Synced;
    $this->updatedAt = new \DateTimeImmutable();
}

public function getSemanticCurrentVersion(): ?SemanticVersion
{
    try {
        return SemanticVersion::parse($this->currentVersion);
    } catch (\InvalidArgumentException) {
        return null;
    }
}

public function getSemanticLatestVersion(): ?SemanticVersion
{
    try {
        return SemanticVersion::parse($this->latestVersion);
    } catch (\InvalidArgumentException) {
        return null;
    }
}

private function determineGapType(string $previous, string $new): string
{
    try {
        $prev = SemanticVersion::parse($previous);
        $next = SemanticVersion::parse($new);

        return match (true) {
            $prev->getMajorGap($next) > 0 => 'major',
            $prev->getMinorGap($next) > 0 => 'minor',
            default => 'patch',
        };
    } catch (\InvalidArgumentException) {
        return 'unknown';
    }
}
```

Required imports to add at top of file:
```php
use App\Dependency\Domain\Event\DependencyCreated;
use App\Dependency\Domain\Event\DependencyUpgraded;
use App\Dependency\Domain\Event\VulnerabilityDetected;
use App\Dependency\Domain\Event\VulnerabilityResolved;
use App\Dependency\Domain\Model\VulnerabilityStatus;
use App\Dependency\Domain\ValueObject\CveId;
use App\Dependency\Domain\ValueObject\SemanticVersion;
use App\Shared\Domain\Model\RecordsDomainEvents;
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Dependency/Domain/Model/DependencyAggregateTest.php`
Expected: 10 tests PASS

- [ ] **Step 5: Run existing Dependency tests to check for regressions**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Dependency/`
Expected: All existing tests PASS. The `DependencyCreated` event constructor change may break existing handler tests — fix by updating stub event dispatches to include the new parameters.

- [ ] **Step 6: Fix regressions in existing tests**

Update test files that create `DependencyCreated` events or assert on them. In handler tests, update the event constructor calls to include `packageManager`, `currentVersion`, and `projectId` parameters.

- [ ] **Step 7: Run full Dependency test suite again**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Dependency/`
Expected: All tests PASS

- [ ] **Step 8: Commit**

```bash
git add backend/src/Dependency/Domain/Model/Dependency.php backend/src/Dependency/Domain/Event/ backend/tests/Unit/Dependency/
git commit -m "feat(dependency): enrich Dependency aggregate root with domain behavior and event recording"
```

---

### Task 11: QueryableSpecificationInterface + updated composites

**Files:**
- Create: `backend/src/Shared/Domain/Specification/QueryableSpecificationInterface.php`
- Modify: `backend/src/Shared/Domain/Specification/AndSpecification.php`
- Modify: `backend/src/Shared/Domain/Specification/OrSpecification.php`
- Modify: `backend/src/Shared/Domain/Specification/NotSpecification.php`
- Test: `backend/tests/Unit/Shared/Domain/Specification/QueryableSpecificationTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Shared\Domain\Specification\AndSpecification;
use App\Shared\Domain\Specification\NotSpecification;
use App\Shared\Domain\Specification\OrSpecification;
use App\Shared\Domain\Specification\QueryableSpecificationInterface;
use App\Shared\Domain\Specification\SpecificationInterface;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\CompositeExpression;

function createQueryableSpec(bool $satisfies, string $field, mixed $value): QueryableSpecificationInterface
{
    return new class ($satisfies, $field, $value) implements QueryableSpecificationInterface {
        public function __construct(
            private readonly bool $satisfies,
            private readonly string $field,
            private readonly mixed $value,
        ) {
        }

        public function isSatisfiedBy(mixed $candidate): bool
        {
            return $this->satisfies;
        }

        public function toDoctrineCriteria(): Criteria
        {
            return Criteria::create()->andWhere(Criteria::expr()->eq($this->field, $this->value));
        }
    };
}

describe('QueryableSpecificationInterface composites', function () {
    describe('AndSpecification', function () {
        it('combines criteria with AND', function () {
            $specA = \createQueryableSpec(true, 'status', 'active');
            $specB = \createQueryableSpec(true, 'type', 'runtime');

            $and = new AndSpecification([$specA, $specB]);
            $criteria = $and->toDoctrineCriteria();

            expect($criteria)->toBeInstanceOf(Criteria::class)
                ->and($criteria->getWhereExpression())->toBeInstanceOf(CompositeExpression::class);
        });

        it('satisfiedBy returns true only when all match', function () {
            $specA = \createQueryableSpec(true, 'a', 1);
            $specB = \createQueryableSpec(false, 'b', 2);

            $and = new AndSpecification([$specA, $specB]);

            expect($and->isSatisfiedBy(new \stdClass()))->toBeFalse();
        });
    });

    describe('OrSpecification', function () {
        it('combines criteria with OR', function () {
            $specA = \createQueryableSpec(true, 'status', 'active');
            $specB = \createQueryableSpec(true, 'status', 'pending');

            $or = new OrSpecification([$specA, $specB]);
            $criteria = $or->toDoctrineCriteria();

            expect($criteria)->toBeInstanceOf(Criteria::class)
                ->and($criteria->getWhereExpression())->toBeInstanceOf(CompositeExpression::class);
        });

        it('satisfiedBy returns true when any match', function () {
            $specA = \createQueryableSpec(false, 'a', 1);
            $specB = \createQueryableSpec(true, 'b', 2);

            $or = new OrSpecification([$specA, $specB]);

            expect($or->isSatisfiedBy(new \stdClass()))->toBeTrue();
        });
    });

    describe('NotSpecification', function () {
        it('negates criteria', function () {
            $spec = \createQueryableSpec(true, 'status', 'deprecated');
            $not = new NotSpecification($spec);

            $criteria = $not->toDoctrineCriteria();

            expect($criteria)->toBeInstanceOf(Criteria::class);
        });

        it('satisfiedBy negates inner spec', function () {
            $spec = \createQueryableSpec(true, 'a', 1);
            $not = new NotSpecification($spec);

            expect($not->isSatisfiedBy(new \stdClass()))->toBeFalse();
        });
    });

    describe('composition', function () {
        it('Not(Not(A)) is equivalent to A', function () {
            $spec = \createQueryableSpec(true, 'a', 1);
            $doubleNot = new NotSpecification(new NotSpecification($spec));

            $candidate = new \stdClass();

            expect($doubleNot->isSatisfiedBy($candidate))->toBe($spec->isSatisfiedBy($candidate));
        });
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Shared/Domain/Specification/QueryableSpecificationTest.php`
Expected: FAIL — interface not found

- [ ] **Step 3: Write QueryableSpecificationInterface**

```php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Specification;

use Doctrine\Common\Collections\Criteria;

interface QueryableSpecificationInterface extends SpecificationInterface
{
    public function toDoctrineCriteria(): Criteria;
}
```

- [ ] **Step 4: Update AndSpecification to support Doctrine criteria**

Modify `backend/src/Shared/Domain/Specification/AndSpecification.php` to implement `QueryableSpecificationInterface` and add `toDoctrineCriteria()`:

```php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Specification;

use Doctrine\Common\Collections\Criteria;

final readonly class AndSpecification implements QueryableSpecificationInterface
{
    /** @param list<SpecificationInterface> $specifications */
    public function __construct(
        private array $specifications,
    ) {
    }

    #[\Override]
    public function isSatisfiedBy(mixed $candidate): bool
    {
        foreach ($this->specifications as $specification) {
            if (!$specification->isSatisfiedBy($candidate)) {
                return false;
            }
        }

        return true;
    }

    #[\Override]
    public function toDoctrineCriteria(): Criteria
    {
        $criteria = Criteria::create();

        foreach ($this->specifications as $specification) {
            if ($specification instanceof QueryableSpecificationInterface) {
                $inner = $specification->toDoctrineCriteria();
                $expr = $inner->getWhereExpression();

                if ($expr !== null) {
                    $criteria->andWhere($expr);
                }
            }
        }

        return $criteria;
    }
}
```

- [ ] **Step 5: Update OrSpecification**

```php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Specification;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\CompositeExpression;

final readonly class OrSpecification implements QueryableSpecificationInterface
{
    /** @param list<SpecificationInterface> $specifications */
    public function __construct(
        private array $specifications,
    ) {
    }

    #[\Override]
    public function isSatisfiedBy(mixed $candidate): bool
    {
        foreach ($this->specifications as $specification) {
            if ($specification->isSatisfiedBy($candidate)) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function toDoctrineCriteria(): Criteria
    {
        $expressions = [];

        foreach ($this->specifications as $specification) {
            if ($specification instanceof QueryableSpecificationInterface) {
                $expr = $specification->toDoctrineCriteria()->getWhereExpression();

                if ($expr !== null) {
                    $expressions[] = $expr;
                }
            }
        }

        $criteria = Criteria::create();

        if ($expressions !== []) {
            $criteria->andWhere(new CompositeExpression(CompositeExpression::TYPE_OR, $expressions));
        }

        return $criteria;
    }
}
```

- [ ] **Step 6: Update NotSpecification**

```php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Specification;

use Doctrine\Common\Collections\Criteria;

final readonly class NotSpecification implements QueryableSpecificationInterface
{
    public function __construct(
        private SpecificationInterface $specification,
    ) {
    }

    #[\Override]
    public function isSatisfiedBy(mixed $candidate): bool
    {
        return !$this->specification->isSatisfiedBy($candidate);
    }

    #[\Override]
    public function toDoctrineCriteria(): Criteria
    {
        $criteria = Criteria::create();

        if ($this->specification instanceof QueryableSpecificationInterface) {
            $expr = $this->specification->toDoctrineCriteria()->getWhereExpression();

            if ($expr !== null) {
                $criteria->andWhere(Criteria::expr()->not($expr));
            }
        }

        return $criteria;
    }
}
```

- [ ] **Step 7: Run tests**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Shared/Domain/Specification/QueryableSpecificationTest.php`
Expected: 7 tests PASS

- [ ] **Step 8: Run existing Shared spec tests for regressions**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Shared/`
Expected: All PASS

- [ ] **Step 9: Commit**

```bash
git add backend/src/Shared/Domain/Specification/ backend/tests/Unit/Shared/Domain/Specification/
git commit -m "feat(shared): add QueryableSpecificationInterface with Doctrine criteria support"
```

---

### Task 12: New Dependency Specifications

**Files:**
- Modify: `backend/src/Dependency/Domain/Specification/IsOutdatedSpecification.php` — implement QueryableSpecificationInterface
- Modify: `backend/src/Dependency/Domain/Specification/IsDeprecatedSpecification.php` — implement QueryableSpecificationInterface
- Modify: `backend/src/Dependency/Domain/Specification/HasCriticalVulnerabilitySpecification.php` — implement QueryableSpecificationInterface
- Create: `backend/src/Dependency/Domain/Specification/HasSeverityAboveSpecification.php`
- Create: `backend/src/Dependency/Domain/Specification/HasVersionGapAboveSpecification.php`
- Create: `backend/src/Dependency/Domain/Specification/HasUnpatchedVulnerabilitySpecification.php`
- Create: `backend/src/Dependency/Domain/Specification/IsStaleSpecification.php`
- Create: `backend/src/Dependency/Domain/Specification/BelongsToProjectSpecification.php`
- Create: `backend/src/Dependency/Domain/Specification/HealthBelowSpecification.php`
- Test: `backend/tests/Unit/Dependency/Domain/Specification/SpecificationsTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\RegistryStatus;
use App\Dependency\Domain\Model\Severity;
use App\Dependency\Domain\Model\VulnerabilityStatus;
use App\Dependency\Domain\Specification\BelongsToProjectSpecification;
use App\Dependency\Domain\Specification\HasCriticalVulnerabilitySpecification;
use App\Dependency\Domain\Specification\HasSeverityAboveSpecification;
use App\Dependency\Domain\Specification\HasUnpatchedVulnerabilitySpecification;
use App\Dependency\Domain\Specification\HasVersionGapAboveSpecification;
use App\Dependency\Domain\Specification\HealthBelowSpecification;
use App\Dependency\Domain\Specification\IsDeprecatedSpecification;
use App\Dependency\Domain\Specification\IsOutdatedSpecification;
use App\Dependency\Domain\Specification\IsStaleSpecification;
use App\Dependency\Domain\Service\DependencyHealthCalculator;
use App\Shared\Domain\Specification\AndSpecification;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Uid\Uuid;

function specTestDep(
    string $currentVersion = '1.0.0',
    string $latestVersion = '1.0.0',
    bool $isOutdated = false,
    RegistryStatus $status = RegistryStatus::Synced,
    ?Uuid $projectId = null,
): Dependency {
    $dep = Dependency::create(
        name: 'test/pkg',
        currentVersion: $currentVersion,
        latestVersion: $latestVersion,
        ltsVersion: $latestVersion,
        packageManager: PackageManager::Composer,
        type: DependencyType::Runtime,
        isOutdated: $isOutdated,
        projectId: $projectId ?? Uuid::v4(),
    );
    $dep->markRegistryStatus($status);

    return $dep;
}

describe('IsOutdatedSpecification', function () {
    it('satisfied when outdated', function () {
        $dep = \specTestDep(isOutdated: true);
        expect((new IsOutdatedSpecification())->isSatisfiedBy($dep))->toBeTrue();
    });

    it('not satisfied when up to date', function () {
        $dep = \specTestDep(isOutdated: false);
        expect((new IsOutdatedSpecification())->isSatisfiedBy($dep))->toBeFalse();
    });

    it('generates Doctrine criteria', function () {
        $criteria = (new IsOutdatedSpecification())->toDoctrineCriteria();
        expect($criteria)->toBeInstanceOf(Criteria::class)
            ->and($criteria->getWhereExpression())->not->toBeNull();
    });
});

describe('IsDeprecatedSpecification', function () {
    it('satisfied when deprecated', function () {
        $dep = \specTestDep(status: RegistryStatus::Deprecated);
        expect((new IsDeprecatedSpecification())->isSatisfiedBy($dep))->toBeTrue();
    });

    it('generates Doctrine criteria', function () {
        $criteria = (new IsDeprecatedSpecification())->toDoctrineCriteria();
        expect($criteria)->toBeInstanceOf(Criteria::class);
    });
});

describe('HasSeverityAboveSpecification', function () {
    it('satisfied when vulnerability above threshold exists', function () {
        $dep = \specTestDep();
        $dep->reportVulnerability('CVE-2024-11111', Severity::Critical, 'Test', 'Desc', '2.0.0');

        expect((new HasSeverityAboveSpecification(Severity::High))->isSatisfiedBy($dep))->toBeTrue();
    });

    it('not satisfied when all vulns below threshold', function () {
        $dep = \specTestDep();
        $dep->reportVulnerability('CVE-2024-22222', Severity::Low, 'Test', 'Desc', '2.0.0');

        expect((new HasSeverityAboveSpecification(Severity::High))->isSatisfiedBy($dep))->toBeFalse();
    });

    it('not satisfied when no vulnerabilities', function () {
        $dep = \specTestDep();
        expect((new HasSeverityAboveSpecification(Severity::Low))->isSatisfiedBy($dep))->toBeFalse();
    });
});

describe('HasVersionGapAboveSpecification', function () {
    it('satisfied when major gap above threshold', function () {
        $dep = \specTestDep('1.0.0', '4.0.0');
        expect((new HasVersionGapAboveSpecification('major', 2))->isSatisfiedBy($dep))->toBeTrue();
    });

    it('not satisfied when gap below threshold', function () {
        $dep = \specTestDep('1.0.0', '2.0.0');
        expect((new HasVersionGapAboveSpecification('major', 2))->isSatisfiedBy($dep))->toBeFalse();
    });

    it('handles non-parseable versions', function () {
        $dep = \specTestDep('dev-main', '2.0.0');
        expect((new HasVersionGapAboveSpecification('major', 1))->isSatisfiedBy($dep))->toBeFalse();
    });
});

describe('HasUnpatchedVulnerabilitySpecification', function () {
    it('satisfied when open vuln exists', function () {
        $dep = \specTestDep();
        $dep->reportVulnerability('CVE-2024-33333', Severity::High, 'Test', 'Desc', '2.0.0');

        expect((new HasUnpatchedVulnerabilitySpecification())->isSatisfiedBy($dep))->toBeTrue();
    });

    it('not satisfied when all resolved', function () {
        $dep = \specTestDep();
        $dep->reportVulnerability('CVE-2024-44444', Severity::High, 'Test', 'Desc', '2.0.0');
        $dep->resolveVulnerability(\App\Dependency\Domain\ValueObject\CveId::fromString('CVE-2024-44444'), '2.0.0');

        expect((new HasUnpatchedVulnerabilitySpecification())->isSatisfiedBy($dep))->toBeFalse();
    });
});

describe('BelongsToProjectSpecification', function () {
    it('satisfied when project matches', function () {
        $projectId = Uuid::v4();
        $dep = \specTestDep(projectId: $projectId);

        expect((new BelongsToProjectSpecification($projectId))->isSatisfiedBy($dep))->toBeTrue();
    });

    it('not satisfied when project differs', function () {
        $dep = \specTestDep(projectId: Uuid::v4());

        expect((new BelongsToProjectSpecification(Uuid::v4()))->isSatisfiedBy($dep))->toBeFalse();
    });
});

describe('HealthBelowSpecification', function () {
    it('satisfied when health score below threshold', function () {
        $dep = \specTestDep('1.0.0', '5.0.0');

        expect((new HealthBelowSpecification(50, new DependencyHealthCalculator()))->isSatisfiedBy($dep))->toBeTrue();
    });

    it('not satisfied when health is good', function () {
        $dep = \specTestDep('1.0.0', '1.0.0');

        expect((new HealthBelowSpecification(50, new DependencyHealthCalculator()))->isSatisfiedBy($dep))->toBeFalse();
    });
});

describe('Specification composition', function () {
    it('composes outdated AND has severity above', function () {
        $dep = \specTestDep('1.0.0', '3.0.0', isOutdated: true);
        $dep->reportVulnerability('CVE-2024-55555', Severity::Critical, 'Test', 'Desc', '2.0.0');

        $spec = new AndSpecification([
            new IsOutdatedSpecification(),
            new HasSeverityAboveSpecification(Severity::High),
        ]);

        expect($spec->isSatisfiedBy($dep))->toBeTrue();
    });

    it('composite generates combined Doctrine criteria', function () {
        $spec = new AndSpecification([
            new IsOutdatedSpecification(),
            new BelongsToProjectSpecification(Uuid::v4()),
        ]);

        $criteria = $spec->toDoctrineCriteria();

        expect($criteria)->toBeInstanceOf(Criteria::class)
            ->and($criteria->getWhereExpression())->not->toBeNull();
    });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Dependency/Domain/Specification/SpecificationsTest.php`
Expected: FAIL — new classes not found

- [ ] **Step 3: Update existing specs to implement QueryableSpecificationInterface**

Update `IsOutdatedSpecification.php`:
```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Specification;

use App\Dependency\Domain\Model\Dependency;
use App\Shared\Domain\Specification\QueryableSpecificationInterface;
use Doctrine\Common\Collections\Criteria;

final readonly class IsOutdatedSpecification implements QueryableSpecificationInterface
{
    #[\Override]
    public function isSatisfiedBy(mixed $candidate): bool
    {
        \assert($candidate instanceof Dependency);

        return $candidate->isOutdated();
    }

    #[\Override]
    public function toDoctrineCriteria(): Criteria
    {
        return Criteria::create()->andWhere(Criteria::expr()->eq('isOutdated', true));
    }
}
```

Update `IsDeprecatedSpecification.php`:
```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Specification;

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\RegistryStatus;
use App\Shared\Domain\Specification\QueryableSpecificationInterface;
use Doctrine\Common\Collections\Criteria;

final readonly class IsDeprecatedSpecification implements QueryableSpecificationInterface
{
    #[\Override]
    public function isSatisfiedBy(mixed $candidate): bool
    {
        \assert($candidate instanceof Dependency);

        return $candidate->getRegistryStatus() === RegistryStatus::Deprecated;
    }

    #[\Override]
    public function toDoctrineCriteria(): Criteria
    {
        return Criteria::create()->andWhere(Criteria::expr()->eq('registryStatus', RegistryStatus::Deprecated));
    }
}
```

Update `HasCriticalVulnerabilitySpecification.php`:
```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Specification;

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\Severity;
use App\Shared\Domain\Specification\QueryableSpecificationInterface;
use Doctrine\Common\Collections\Criteria;

final readonly class HasCriticalVulnerabilitySpecification implements QueryableSpecificationInterface
{
    #[\Override]
    public function isSatisfiedBy(mixed $candidate): bool
    {
        \assert($candidate instanceof Dependency);

        foreach ($candidate->getVulnerabilities() as $vulnerability) {
            if ($vulnerability->getSeverity() === Severity::Critical) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function toDoctrineCriteria(): Criteria
    {
        return Criteria::create();
    }
}
```

- [ ] **Step 4: Write HasSeverityAboveSpecification**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Specification;

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\Severity;
use App\Shared\Domain\Specification\QueryableSpecificationInterface;
use Doctrine\Common\Collections\Criteria;

final readonly class HasSeverityAboveSpecification implements QueryableSpecificationInterface
{
    public function __construct(
        private Severity $threshold,
    ) {
    }

    #[\Override]
    public function isSatisfiedBy(mixed $candidate): bool
    {
        \assert($candidate instanceof Dependency);

        foreach ($candidate->getVulnerabilities() as $vulnerability) {
            if ($vulnerability->getSeverity()->isHigherThan($this->threshold) || $vulnerability->getSeverity() === $this->threshold) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function toDoctrineCriteria(): Criteria
    {
        return Criteria::create();
    }
}
```

- [ ] **Step 5: Write HasVersionGapAboveSpecification**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Specification;

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\ValueObject\SemanticVersion;
use App\Shared\Domain\Specification\QueryableSpecificationInterface;
use Doctrine\Common\Collections\Criteria;

final readonly class HasVersionGapAboveSpecification implements QueryableSpecificationInterface
{
    public function __construct(
        private string $gapType,
        private int $threshold,
    ) {
    }

    #[\Override]
    public function isSatisfiedBy(mixed $candidate): bool
    {
        \assert($candidate instanceof Dependency);

        $current = $candidate->getSemanticCurrentVersion();
        $latest = $candidate->getSemanticLatestVersion();

        if ($current === null || $latest === null) {
            return false;
        }

        $gap = match ($this->gapType) {
            'major' => $current->getMajorGap($latest),
            'minor' => $current->getMinorGap($latest),
            'patch' => $current->getPatchGap($latest),
            default => 0,
        };

        return $gap > $this->threshold;
    }

    #[\Override]
    public function toDoctrineCriteria(): Criteria
    {
        return Criteria::create();
    }
}
```

- [ ] **Step 6: Write HasUnpatchedVulnerabilitySpecification**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Specification;

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\VulnerabilityStatus;
use App\Shared\Domain\Specification\QueryableSpecificationInterface;
use Doctrine\Common\Collections\Criteria;

final readonly class HasUnpatchedVulnerabilitySpecification implements QueryableSpecificationInterface
{
    #[\Override]
    public function isSatisfiedBy(mixed $candidate): bool
    {
        \assert($candidate instanceof Dependency);

        foreach ($candidate->getVulnerabilities() as $vulnerability) {
            if ($vulnerability->getStatus() !== VulnerabilityStatus::Fixed && $vulnerability->getStatus() !== VulnerabilityStatus::Ignored) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function toDoctrineCriteria(): Criteria
    {
        return Criteria::create();
    }
}
```

- [ ] **Step 7: Write IsStaleSpecification**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Specification;

use App\Dependency\Domain\Model\Dependency;
use App\Shared\Domain\Specification\QueryableSpecificationInterface;
use Doctrine\Common\Collections\Criteria;

final readonly class IsStaleSpecification implements QueryableSpecificationInterface
{
    public function __construct(
        private int $days,
    ) {
    }

    #[\Override]
    public function isSatisfiedBy(mixed $candidate): bool
    {
        \assert($candidate instanceof Dependency);

        $threshold = new \DateTimeImmutable(\sprintf('-%d days', $this->days));

        return $candidate->getUpdatedAt() < $threshold;
    }

    #[\Override]
    public function toDoctrineCriteria(): Criteria
    {
        $threshold = new \DateTimeImmutable(\sprintf('-%d days', $this->days));

        return Criteria::create()->andWhere(Criteria::expr()->lt('updatedAt', $threshold));
    }
}
```

- [ ] **Step 8: Write BelongsToProjectSpecification**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Specification;

use App\Dependency\Domain\Model\Dependency;
use App\Shared\Domain\Specification\QueryableSpecificationInterface;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Uid\Uuid;

final readonly class BelongsToProjectSpecification implements QueryableSpecificationInterface
{
    public function __construct(
        private Uuid $projectId,
    ) {
    }

    #[\Override]
    public function isSatisfiedBy(mixed $candidate): bool
    {
        \assert($candidate instanceof Dependency);

        return $candidate->getProjectId()->equals($this->projectId);
    }

    #[\Override]
    public function toDoctrineCriteria(): Criteria
    {
        return Criteria::create()->andWhere(Criteria::expr()->eq('projectId', $this->projectId));
    }
}
```

- [ ] **Step 9: Write HealthBelowSpecification**

```php
<?php

declare(strict_types=1);

namespace App\Dependency\Domain\Specification;

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Service\DependencyHealthCalculator;
use App\Shared\Domain\Specification\QueryableSpecificationInterface;
use Doctrine\Common\Collections\Criteria;

final readonly class HealthBelowSpecification implements QueryableSpecificationInterface
{
    public function __construct(
        private int $threshold,
        private DependencyHealthCalculator $calculator,
    ) {
    }

    #[\Override]
    public function isSatisfiedBy(mixed $candidate): bool
    {
        \assert($candidate instanceof Dependency);

        return $this->calculator->calculate($candidate)->getScore() < $this->threshold;
    }

    #[\Override]
    public function toDoctrineCriteria(): Criteria
    {
        return Criteria::create();
    }
}
```

- [ ] **Step 10: Run tests**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Dependency/Domain/Specification/SpecificationsTest.php`
Expected: All tests PASS

- [ ] **Step 11: Run full Dependency test suite for regressions**

Run: `docker compose exec backend vendor/bin/pest tests/Unit/Dependency/`
Expected: All tests PASS

- [ ] **Step 12: Commit**

```bash
git add backend/src/Dependency/Domain/Specification/ backend/tests/Unit/Dependency/Domain/Specification/
git commit -m "feat(dependency): add advanced dual-purpose specifications with composite support"
```

---

## Final Verification

- [ ] **Run full backend test suite**

Run: `docker compose exec backend vendor/bin/pest`
Expected: All tests PASS

- [ ] **Run PHPStan**

Run: `docker compose exec backend vendor/bin/phpstan analyse`
Expected: No errors at max level

- [ ] **Run Deptrac**

Run: `docker compose exec backend vendor/bin/deptrac`
Expected: No layer violations
