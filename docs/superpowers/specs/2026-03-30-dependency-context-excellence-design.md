# Design: Dependency Context — Excellence DDD & Clean Code

**Date**: 2026-03-30
**Objectif**: Transformer le bounded context Dependency en vitrine technologique avec patterns avancés, DDD riche, clean code et couverture maximale.
**Approche**: DDD Tactical Patterns — enrichir le domaine avec des patterns justifiés par le métier, puis répliquer sur les autres contextes.
**Scope**: Backend domain/application/infra + Frontend pages/composables/stores/tests

---

## 1. Domain Layer — Value Objects riches

### 1.1 `SemanticVersion` Value Object

- Parse `major.minor.patch(-prerelease)?`
- Implémente `Stringable`, `\JsonSerializable`
- Doctrine custom type `semantic_version`
- Methodes:
  - `isNewerThan(self): bool` — comparaison sémantique avec support pre-release
  - `isCompatibleWith(self): bool` — même major
  - `getMajorGap(self): int`
  - `getMinorGap(self): int`
  - `getPatchGap(self): int`
  - `isPreRelease(): bool`
  - `static parse(string): self` — factory avec validation
- Algo: comparaison segment par segment, pre-release < release, support suffixes alpha/beta/rc

### 1.2 `CveId` Value Object

- Validation regex: `CVE-\d{4}-\d{4,}`
- Immutable, self-validating
- Methodes:
  - `getYear(): int`
  - `getSequence(): int`
  - `static fromString(string): self`

### 1.3 `VulnerabilityScore` Value Object

- Score CVSS-like calculé depuis `Severity` + facteurs contextuels
- Methodes:
  - `toFloat(): float` — score 0.0-10.0
  - `isAboveThreshold(float): bool`
  - `getRiskLevel(): RiskLevel` — enum Critical/High/Medium/Low/None
- Comparable entre vulnérabilités

### 1.4 `DependencyHealth` Value Object

- Composite: combine outdated + vulnerability count + version gap + registry status
- Algo weighted scoring:
  - Version gap: major=40, minor=20, patch=5
  - Vulnerability: critical=50, high=30, medium=15, low=5
  - Registry: deprecated=30, not_found=20
  - Normalize 0-100 (100 = healthy)
- Methodes:
  - `getScore(): int` — 0-100
  - `getRiskLevel(): RiskLevel`
  - `isHealthy(): bool` — score >= 70

---

## 2. Domain Services & Aggregate Root enrichi

### 2.1 Aggregate Root `Dependency` enrichi

Invariants proteges dans l'aggregate:
- `upgrade(newVersion: SemanticVersion)` — met a jour, recalcule isOutdated, emet `DependencyUpgraded`
- `reportVulnerability(vuln)` — ajoute avec verification doublon CVE, emet `VulnerabilityDetected`
- `resolveVulnerability(cveId, patchedVersion)` — marque fixed, emet `VulnerabilityResolved`
- `calculateHealth(): DependencyHealth` — calcul interne avec acces vulnerabilites
- `markDeprecated()` / `markSynced()` — transitions d'etat explicites

### 2.2 `VersionComparisonService` (Domain Service)

- Compare `SemanticVersion` avec logique complexe
- Determine `isOutdated` via regles metier (pas juste `!=`)
- Pattern **Strategy**: strategies de comparaison par `PackageManager`
  - `NpmVersionStrategy` — semver ranges
  - `ComposerVersionStrategy` — composer semver
  - `PipVersionStrategy` — PEP 440

### 2.3 `DependencyHealthCalculator` (Domain Service)

- Input: `Dependency` aggregate (avec vulnerabilites, versions, status)
- Output: `DependencyHealth` Value Object
- Algo: Weighted Scoring normalise 0-100

### 2.4 `VulnerabilityAssessor` (Domain Service)

- Evalue le risque global d'un ensemble de vulnerabilites
- Pattern **Chain of Responsibility**:
  - `SeverityAssessmentHandler` — evalue par severite
  - `PatchAvailabilityHandler` — bonus si patch disponible
  - `ExploitabilityHandler` — facteur d'exploitabilite
- Output: `RiskAssessment` VO avec `level`, `score`, `recommendations[]`

### 2.5 Domain Events enrichis

- `DependencyUpgraded { dependencyId, previousVersion, newVersion, gapType }`
- `VulnerabilityDetected { dependencyId, cveId, severity, affectedVersion }`
- `VulnerabilityResolved { dependencyId, cveId, patchedVersion }`
- `DependencyHealthChanged { dependencyId, previousScore, newScore, riskLevel }`

---

## 3. Specifications avancées & Query Patterns

### 3.1 Dual-purpose Specification interface

```
SpecificationInterface
  isSatisfiedBy(entity): bool        -- evaluation domain
  toDoctrineCriteria(): Criteria      -- generation query
```

### 3.2 Nouvelles Specifications

| Specification | Logique |
|---|---|
| `HasVersionGapAbove(type, threshold)` | Major/minor gap > N |
| `HasUnpatchedVulnerability` | Open vuln + patchedVersion existe |
| `IsStale(days)` | Pas de sync depuis N jours |
| `HasSeverityAbove(severity)` | Vulns au-dessus d'un seuil |
| `BelongsToProject(projectId)` | Filtre par projet |
| `HealthBelow(score)` | Score sante sous un seuil |

### 3.3 Composition fluide

And/Or/Not compositeurs avec support `toDoctrineCriteria()` recursif.

### 3.4 Repository avec Specification queries

- `findBySpecification(SpecificationInterface, page, perPage): array`
- `countBySpecification(SpecificationInterface): int`

### 3.5 Pattern Interpreter pour les filtres API

`SpecificationFactory` transforme les query params HTTP en arbre de Specifications.

---

## 4. Application Layer — Pipeline, Saga & Policies

### 4.1 Pattern Pipeline pour le Sync

Stages:
1. `FetchRegistryVersionsStage` — appelle le registry
2. `FilterNewVersionsStage` — compare avec versions connues
3. `PersistVersionsStage` — sauvegarde en DB
4. `UpdateDependencyStatusStage` — met a jour via domain service
5. `CalculateHealthStage` — recalcule le health score
6. `NotifyProgressStage` — publie sur Mercure

Interface:
```
SyncStageInterface
  __invoke(SyncContext): SyncContext
```

`SyncContext` est un VO immutable accumulant les resultats.

### 4.2 Saga `DependencySyncSaga`

Orchestration du workflow complet sur `ProjectScannedEvent`:
1. Upsert deps detectees
2. Supprimer stale
3. Sync versions nouvelles
4. Recalculer sante
5. Emettre `ProjectHealthRecalculated`

Etats: Started, DepsUpserted, StaleRemoved, VersionsSyncing, HealthCalculated, Completed, Failed.

### 4.3 Policies

| Policy | Regle |
|---|---|
| `SyncThrottlePolicy` | Max 1 sync/package/heure |
| `DeprecationPolicy` | 3x not_found consecutifs -> deprecated |
| `VulnerabilityEscalationPolicy` | Critical + no patch -> notification immediate |
| `HealthAlertPolicy` | Score < 30 -> alerte |

---

## 5. Infrastructure — Registry Strategy, Repository & Performance

### 5.1 Strategy pattern formalise pour les registries

- `PackageRegistryStrategy` avec registration via Symfony Compiler Pass
- Chaque adapter se declare via `#[AsPackageRegistry(PackageManager::Npm)]`
- Fail-fast si aucun adapter

### 5.2 `PypiRegistryAdapter`

Nouveau adapter pour pip — demontre la scalabilite du Strategy pattern.

### 5.3 Fix N+2 dans ListDependenciesHandler

Single query avec LEFT JOIN sur `dependency_version` pour charger `current_version_released_at` et `latest_version_released_at`.

### 5.4 `getStats()` en single query

`COUNT` conditionnel avec `FILTER (WHERE ...)` au lieu de 3 queries separees.

### 5.5 Cache amelioree

- Cache warming apres sync
- Cache key basee sur hash des Specifications
- TTL differencies: stats=5min, listes=1min, detail=30s

---

## 6. Frontend — Refactoring & Patterns avances

### 6.1 Extraction composables

| Composable | Responsabilite | Source |
|---|---|---|
| `useDependencyGrouping` | Grouper deps par nom, trier | DependencyList computed |
| `useDependencyStats` | Health score, gap stats | DependencyList computed |
| `useDependencyExport` | CSV + PDF via Strategy | DependencyList methods |
| `useDependencyFilters` | Etendre useListFiltering | DependencyList refs |

DependencyList.vue: 626 -> ~150 lignes.

### 6.2 Migration stores vers createCrudStore

Remplacer les stores manuels dependency.ts et vulnerability.ts par `createCrudStore` factory + extensions custom.

### 6.3 Migration forms vers useForm

Toutes les forms utilisent `useForm` composable. Plus de 8-10 refs separees.

### 6.4 Pattern Strategy pour l'export

```
ExportStrategy interface
  export(data, options): void

CsvExportStrategy
PdfExportStrategy
```

### 6.5 Pattern State Machine pour les formulaires

```
FormState =
  | { mode: 'create' }
  | { mode: 'edit'; entityId: string; loaded: boolean }
  | { mode: 'submitting' }
  | { mode: 'success'; entityId: string }
  | { mode: 'error'; message: string }
```

### 6.6 Types enrichis

- Enums TypeScript importes (plus de string literals hardcodes)
- Discriminated unions pour reponses API
- Types utilitaires: `DependencyRow`, `DependencyGroup`

### 6.7 DependencyFilters.vue

Single model object au lieu de 5 defineModel separes.

---

## 7. Tests — Coverage 90%+ & Mutation 85%+

### 7.1 Backend par couche

| Couche | Type | Objectif |
|---|---|---|
| Value Objects | Unit + property-based | 100% mutation |
| Domain Services | Unit | 95% coverage |
| Specifications | Unit + composition | 100% mutation |
| Aggregate Root | Unit | 95% mutation |
| Pipeline Stages | Unit | 90% coverage |
| Policies | Unit | 100% mutation |
| Handlers | Unit | 85% coverage |
| Repository | Integration | 80% coverage |
| Registry Adapters | Integration | 85% coverage |
| Controllers | Integration | 80% coverage |

### 7.2 Backend tests remarquables

- **Property-based testing** sur SemanticVersion (irreflexivite, transitivite, roundtrip)
- **Specification composition testing** (equivalence domain/query)
- **Pipeline testing** (stages isoles + pipeline complet)

### 7.3 Frontend par cible

| Cible | Type | Objectif |
|---|---|---|
| Composables | Unit | 95% mutation |
| Stores | Unit | 90% coverage |
| Services | Unit | 90% coverage |
| Components | Component | 85% coverage |
| Pages | Integration | 80% coverage |
| E2E | Playwright | 5 scenarios |

### 7.4 Frontend test utilities

- `createTestMount()` — wrapper avec router/i18n/pinia pre-configures
- `factories/` — createDependency(), createVulnerability() avec defaults
- `mocks/` — services et stores reutilisables

### 7.5 Metriques cibles

| Metrique | Actuel (estime) | Cible |
|---|---|---|
| Backend coverage | ~60% | 90% |
| Backend MSI | ~70% | 85% |
| Frontend coverage | ~65% | 90% |
| Frontend Stryker | ~60% | 85% |
| E2E Dependency | 0 | 5 |

---

## 8. Ordre d'execution

### Phase 1 — Dependency Backend Domain

1.1 Value Objects: SemanticVersion, CveId, VulnerabilityScore, DependencyHealth
1.2 Aggregate Root enrichi: invariants, transitions, events riches
1.3 Domain Services: VersionComparisonService, DependencyHealthCalculator, VulnerabilityAssessor
1.4 Specifications avancees: dual-purpose, compositions
1.5 Tests domain: unit + property-based, mutation 85%+

### Phase 2 — Dependency Backend Application & Infra

2.1 Pipeline pattern: SyncPipeline avec 6 stages
2.2 Saga: DependencySyncSaga
2.3 Policies: SyncThrottle, Deprecation, Escalation, HealthAlert
2.4 Registry Strategy: attribute registration, PypiAdapter
2.5 Repository ameliore: N+2 fix, single-query stats, Specification queries
2.6 Migration DB: nouveaux champs
2.7 Tests application + integration

### Phase 3 — Dependency Frontend

3.1 Types enrichis: enums TS, discriminated unions, state machine types
3.2 Stores: migration vers createCrudStore + extensions
3.3 Composables: grouping, stats, export, filters
3.4 Forms: migration useForm + state machine
3.5 Pages: DependencyList -> 150 lignes, DependencyForm simplifie
3.6 Components: DependencyFilters single model, ExportButton Strategy
3.7 Tests frontend: composables, stores, components, 5 E2E

### Phase 4 — Replication autres contextes

4.1 Catalog: Value Objects TechStack, health scoring
4.2 Identity: Password policy
4.3 Activity: domain events, ports
4.4 VersionRegistry: Deptrac, resolvers enrichis
4.5 Shared: Specification framework, ports

### Volume estime

| Phase | Fichiers nouveaux | Fichiers modifies | Tests nouveaux |
|---|---|---|---|
| 1 — Domain | ~15 | ~8 | ~25 |
| 2 — App/Infra | ~12 | ~10 | ~20 |
| 3 — Frontend | ~8 | ~12 | ~30 |
| 4 — Replication | ~20 | ~15 | ~30 |
| **Total** | **~55** | **~45** | **~105** |
