# Backend Coverage & Mutation Testing Improvement

**Date**: 2026-03-29
**Status**: Approved

## Context

The Monark backend has 361 source files across 5 bounded contexts with only 101 unit tests (~28% file ratio). No integration or functional tests exist. Mutation testing (Infection) is configured locally but not enforced in CI. The CI gate requires 80% line coverage.

### Current State

| Context    | Source Files | Test Files | File Ratio |
|------------|-------------|------------|------------|
| Dependency | 67          | 19         | 28%        |
| Catalog    | 134         | 42         | 31%        |
| Activity   | 87          | 23         | 26%        |
| Identity   | 50          | 12         | 24%        |
| Shared     | 23          | 4          | 17%        |

### Critical Gaps

- 14 Doctrine repositories with zero tests
- ~26 domain model classes untested
- Controllers tested via consolidated files, not granularly
- 0 integration tests, 0 functional tests
- Mutation Score Indicator (MSI) unknown

## Goals

- **Line coverage**: 28% current -> 80%+ (CI gate)
- **MSI (Infection)**: unknown -> 70%+
- **Covered MSI**: meet 80% gate (already configured in infection.json5)

## Approach: Parallel Work by Layer

Work proceeds by bounded context in priority order: **Dependency -> Catalog -> Activity -> Identity -> Shared**.

Within each context, three axes run in parallel:

### Axe 1 — Domain & Application (Unit Tests + Mutation)

**Scope**: Value Objects, Entities, Domain Events, Command Handlers, Query Handlers, DTOs, Event Listeners.

**Method**:
- Write unit tests for all untested domain classes
- Strengthen existing handler tests to kill more mutants
- Use mocks for all Port interfaces (repositories, external services)
- Run Infection after each batch to measure MSI progression

**What to test**:
- Value Object construction, equality, validation, edge cases
- Entity state transitions, invariant enforcement
- Command/Query Handler logic paths, error cases
- DTO mapping correctness
- Event Listener side effects

### Axe 2 — Infrastructure (Unit + Integration)

**Scope**: Doctrine Repositories, API Adapters, external service clients, factories.

**Method**:
- **Unit tests with mocks** for all Adapters (GitLab client, package registries, etc.)
- **Integration tests against PostgreSQL** for all 14 Doctrine repositories via Docker container
- Integration tests use the existing Docker PostgreSQL service
- Each integration test manages its own transaction (begin in setUp, rollback in tearDown)

**Integration test setup**:
- Use Symfony's KernelTestCase for repository integration tests
- Leverage existing test factories for entity creation
- Ensure database schema is up-to-date via migrations before test run

**14 repositories to cover**:
- Identity: DoctrineAccessTokenRepository, DoctrineUserRepository
- Catalog: DoctrineMergeRequestRepository, DoctrineProjectRepository, DoctrineProviderRepository, DoctrineSyncJobRepository, DoctrineTechStackRepository
- Dependency: DoctrineDependencyRepository, DoctrineDependencyVersionRepository, DoctrineVulnerabilityRepository
- Activity: DoctrineActivityEventRepository, DoctrineBuildMetricRepository, DoctrineNotificationRepository, DoctrineSyncTaskRepository

### Axe 3 — Presentation (Unit Tests)

**Scope**: Controllers (HTTP + Console commands).

**Method**:
- Write granular test files (one per controller) replacing consolidated test files
- Test request validation, response structure, error handling
- Mock all application-layer dependencies (handlers, buses)
- Test console commands for correct I/O

## Execution Strategy

### Agent Parallelism

Multiple agents work simultaneously:
- One agent per axe within a bounded context
- Each agent focuses on a specific layer to avoid conflicts
- Code reviewer agent validates after each context is complete

### Workflow Per Context

1. Inventory untested files for the context
2. Dispatch parallel agents (Axe 1, 2, 3)
3. Each agent writes tests, runs them, runs Infection
4. Code review after all axes complete
5. Move to next context

### Mutation Testing Integration

- Run `make test-mutation` after each bounded context
- Track MSI progression per context
- Strengthen weak tests identified by surviving mutants
- Focus on: boundary conditions, null checks, return values, conditional logic

## Test Conventions

- Framework: Pest PHP v4 (existing)
- Test location mirrors source: `tests/Unit/{Context}/{Layer}/...`
- Integration tests: `tests/Integration/{Context}/...`
- Use existing test factories in `tests/Factory/`
- Create new factories as needed for untested entities
- No comments in test code (project convention)
- Descriptive test names using Pest's `it('should ...')` syntax

## Out of Scope

- Functional/E2E tests (API-level)
- Frontend tests
- CI pipeline changes (mutation in CI can be added later)
- PHPStan level increase
- Refactoring production code

## Success Criteria

- [ ] `make test-coverage` passes (80%+ line coverage)
- [ ] `make test-mutation` reports MSI >= 70%
- [ ] All 14 Doctrine repositories have integration tests
- [ ] Each controller has its own dedicated test file
- [ ] All domain Value Objects and Entities have unit tests
- [ ] Zero test failures on `make test`
