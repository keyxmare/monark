# Backend Refactoring — Design Spec

**Date**: 2026-03-29
**Objectif**: Transformer le backend Monark en vitrine technologique (template de reference, outil de demonstration, produit reel) tout en maintenant les seuils 80% coverage / 80% MSI.
**Approche**: Bottom-up — tests d'abord, refactoring ensuite.
**Stack**: Symfony 8 pur (pas d'API Platform), PHP 8.4, Doctrine 3, Pest 4, RabbitMQ.

---

## Etat des lieux

### Points forts
- Architecture hexagonale / DDD avec 4 bounded contexts (Identity, Catalog, Dependency, Activity) + Shared kernel
- CQRS via 3 bus Symfony Messenger (command, query, event)
- Deptrac enforce les frontieres architecturales
- Modeles riches (constructeurs prives, factory methods, transitions d'etat)
- Enums PHP pour les value objects, UUID v7
- 144 fichiers de tests unitaires, 1020 cas

### Problemes identifies
- Tests d'integration et fonctionnels vides (.gitkeep)
- Controllers repartis entre Infrastructure/Controller et Presentation/Controller (inconsistant)
- Pas de documentation API (OpenAPI/Swagger absent)
- Value objects limites a de simples enums sans comportement
- Handlers font trop : validation metier + orchestration + transformation DTO
- Output DTOs couples aux entites via `fromEntity()` statique
- Pas de logging structure, caching, resilience sur les adapters externes
- Pas de health check / readiness endpoints

---

## Section 1 — Uniformisation architecturale

### Corrections
- Migrer tous les controllers vers `Presentation/Controller/` dans chaque bounded context
- Supprimer les dossiers `Infrastructure/Controller/` residuels
- Verifier que Deptrac couvre la couche Presentation explicitement

### Ajouts vitrine
- **OpenAPI/Swagger** via `nelmio/api-doc-bundle` avec annotations sur les controllers
- **Health check** endpoint (`/api/health`) avec verification DB + RabbitMQ
- **Readiness** endpoint (`/api/ready`) — DB, RabbitMQ, providers
- **Versioning API** via prefix `/api/v1/`
- **Rate limiting** via Symfony RateLimiter
- **Response standardisee** : utiliser `ApiResponse` partout de maniere coherente

---

## Section 2 — Renforcement du Domain layer

### Corrections
- Enrichir les value objects avec du comportement (ex: `Severity::isHigherThan()`, `PackageManager` connait ses fichiers manifestes)
- Extraire des Domain Services quand la logique implique plusieurs agregats (ex: logique de scan touchant Project + Dependency + TechStack)
- Ajouter des Guards/Assertions dans les entites (valider les invariants dans les constructeurs)

### Ajouts vitrine
- **Specification pattern** pour les requetes complexes (`OutdatedDependencySpecification`, `CriticalVulnerabilitySpecification`)
- **Value objects types** pour les concepts metier forts : `Email`, `Slug`, `RepositoryUrl` au lieu de simples `string`
- **Domain Events enrichis** avec metadata (timestamp, causation ID, correlation ID)

---

## Section 3 — Couche Application : Command/Query hardening

### Corrections
- Separer la validation metier dans le Domain (guards/specifications), ne garder que l'orchestration dans les handlers
- Les handlers deviennent minces : recuperer, deleguer au domain, persister, dispatcher event
- Remplacer `fromEntity()` par des Mappers dedies (`ProjectMapper::toOutput()`) dans Application — testables independamment

### Ajouts vitrine
- **Middleware de logging** sur les 3 bus — tracabilite complete
- **Middleware de validation** automatique des commands (Symfony Validator sur les command objects)
- **Idempotency** sur les commands async (deduplication par ID sur `ScanProjectCommand`, `SyncProjectMetadataCommand`)

---

## Section 4 — Infrastructure : robustesse et observabilite

### Corrections
- Layer de resilience sur les adapters externes (GitLab API, registries) : retry policy, circuit breaker via `symfony/http-client` retry options
- Logging structure (JSON) avec contexte metier sur les operations critiques

### Ajouts vitrine
- **Cache layer** via Symfony Cache sur les queries frequentes (list projects, dependency versions) avec invalidation sur les commands
- **Structured logging** avec correlation ID propage a travers les bus
- **Doctrine query optimization** : verifier les N+1 queries, ajouter des fetch joins explicites

---

## Section 5 — Strategie de tests

### Tests d'integration (nouveaux)
- **Repositories Doctrine** : queries reelles contre DB de test
- **Bus Messenger** : handlers correctement cables et recevant les messages
- **Event listeners** : chaine complete `ProjectScannedEvent` -> creation des SyncTasks
- **Adapters externes** : mocks HTTP (Symfony HttpClient MockClient) pour GitLab API, registries

### Tests fonctionnels (nouveaux)
- Chaque endpoint API : request complete -> response validee (status code, structure JSON, headers)
- Cas nominaux + erreurs (validation, not found, duplicates)
- Authentification/autorisation sur les endpoints proteges
- Health check et readiness endpoints

### Mutation testing
- Etendre le scope de `mutation-backend.sh` pour couvrir les nouveaux tests
- Viser les mutants sur la logique metier du Domain (specifications, value objects, guards)

---

## Ordre d'execution

### Phase 1 — Tests sur le code existant
1. Tests d'integration des repositories Doctrine
2. Tests d'integration des bus et event listeners
3. Tests fonctionnels de tous les endpoints API existants
4. Verifier que coverage et MSI restent >= 80%

### Phase 2 — Uniformisation architecturale
5. Migrer tous les controllers vers `Presentation/Controller/`
6. Mettre a jour Deptrac pour la couche Presentation
7. Ajouter OpenAPI via nelmio/api-doc-bundle
8. Ajouter versioning `/api/v1/`
9. Health check + readiness endpoints
10. Rate limiting

### Phase 3 — Renforcement Domain + Application
11. Value objects types (`Email`, `Slug`, `RepositoryUrl`)
12. Guards/assertions dans les entites
13. Specification pattern
14. Domain Services
15. Mappers dedies (remplacer `fromEntity()`)
16. Handlers amincis
17. Middlewares bus (logging, validation, correlation ID, idempotency)

### Phase 4 — Infrastructure
18. Retry/circuit breaker sur adapters externes
19. Cache layer + invalidation
20. Logging structure JSON + correlation ID
21. Optimisation queries Doctrine (N+1)

### Phase 5 — Tests de la nouvelle architecture
22. Tests unitaires des nouveaux composants (value objects, specifications, mappers, middlewares)
23. Mise a jour des tests existants impactes par le refactoring
24. Tests d'integration et fonctionnels sur les nouveaux endpoints (health, readiness)
25. Passe finale mutation testing

**Chaque phase est un checkpoint livrable** — le code compile, les tests passent, le CI est vert.
