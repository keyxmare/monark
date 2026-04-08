# Design: Split TechStack into Language + Framework entities

**Date**: 2026-03-30
**Objectif**: Remplacer l'entite TechStack (ecran unique avec onglets) par 2 entites distinctes Language et Framework, avec 2 pages frontend separees dans le menu Gouvernance.
**Motivation**: Confusion UX entre les onglets Languages/Frameworks, navigation peu claire avec un seul point d'entree.

---

## 1. Entites Backend

### `Language` (nouvelle entite)

| Champ | Type | Description |
|---|---|---|
| id | UUID | Primary key |
| name | string(100) | Nom du langage (PHP, Python, Java...) |
| version | string(50) | Version detectee |
| detectedAt | DateTimeImmutable | Date de detection |
| eolDate | DateTimeImmutable (nullable) | Date de fin de support |
| maintenanceStatus | string(20) (nullable) | active, eol (simplifie) |
| projectId | UUID FK | Relation ManyToOne vers Project |
| createdAt | DateTimeImmutable | |
| updatedAt | DateTimeImmutable | |

Suivi simplifie : version + EOL. Pas de LTS gap, pas de version sync.

### `Framework` (nouvelle entite)

| Champ | Type | Description |
|---|---|---|
| id | UUID | Primary key |
| name | string(100) | Nom du framework (Symfony, Django, Spring Boot...) |
| version | string(50) | Version detectee |
| detectedAt | DateTimeImmutable | Date de detection |
| latestLts | string(50) (nullable) | Derniere version LTS connue |
| ltsGap | string(100) (nullable) | Ecart avec la LTS |
| maintenanceStatus | string(20) (nullable) | active, warning, eol |
| eolDate | DateTimeImmutable (nullable) | Date de fin de support |
| versionSyncedAt | DateTimeImmutable (nullable) | Derniere synchro version |
| languageId | UUID FK | Relation ManyToOne vers Language |
| projectId | UUID FK | Relation ManyToOne vers Project |
| createdAt | DateTimeImmutable | |
| updatedAt | DateTimeImmutable | |

Suivi complet : LTS, gap, maintenance status, EOL, version sync.
Relation : un Framework est toujours lie a un Language.

### `TechStack` — supprime

Drop table `catalog_tech_stacks`. Pas de migration de donnees. Le prochain scan de projet repopule les nouvelles tables.

---

## 2. Backend — Fichiers impactes

### Nouveaux fichiers

**Domain:**
- `Catalog/Domain/Model/Language.php` — Entite aggregate, factory create(), RecordsDomainEvents
- `Catalog/Domain/Model/Framework.php` — Entite avec FK Language, factory create(), RecordsDomainEvents
- `Catalog/Domain/Repository/LanguageRepositoryInterface.php`
- `Catalog/Domain/Repository/FrameworkRepositoryInterface.php`

**Application:**
- `Catalog/Application/Command/CreateLanguage.php` + handler
- `Catalog/Application/Command/DeleteLanguage.php` + handler
- `Catalog/Application/Command/CreateFramework.php` + handler
- `Catalog/Application/Command/DeleteFramework.php` + handler
- `Catalog/Application/Query/ListLanguages.php` + handler
- `Catalog/Application/Query/GetLanguage.php` + handler
- `Catalog/Application/Query/ListFrameworks.php` + handler
- `Catalog/Application/Query/GetFramework.php` + handler
- `Catalog/Application/DTO/` — LanguageInput, LanguageOutput, FrameworkInput, FrameworkOutput
- `Catalog/Application/Mapper/LanguageMapper.php`
- `Catalog/Application/Mapper/FrameworkMapper.php`
- `Catalog/Application/Service/FrameworkVersionStatusUpdater.php` — renomme de TechStackVersionStatusUpdater, adapte pour Framework

**Infrastructure:**
- `Catalog/Infrastructure/Persistence/Doctrine/DoctrineLanguageRepository.php`
- `Catalog/Infrastructure/Persistence/Doctrine/DoctrineFrameworkRepository.php`

**Presentation:**
- `Catalog/Presentation/Controller/` — CRUD controllers pour Language et Framework (6-8 controllers)

### Fichiers supprimes

- `Catalog/Domain/Model/TechStack.php`
- `Catalog/Domain/Repository/TechStackRepositoryInterface.php`
- `Catalog/Domain/Event/TechStackVersionStatusUpdated.php`
- `Catalog/Domain/ValueObject/TechStackHealth.php` → `FrameworkHealth.php`
- `Catalog/Domain/Service/TechStackHealthCalculator.php` → `FrameworkHealthCalculator.php`
- `Catalog/Application/Command/CreateTechStack.php` + handler
- `Catalog/Application/Command/DeleteTechStack.php` + handler
- `Catalog/Application/Query/ListTechStacks.php` + handler
- `Catalog/Application/Query/GetTechStack.php` + handler
- `Catalog/Application/DTO/` — TechStack DTOs
- `Catalog/Application/Mapper/TechStackMapper.php`
- `Catalog/Application/Service/TechStackVersionStatusUpdater.php`
- `Catalog/Infrastructure/Persistence/Doctrine/DoctrineTechStackRepository.php`
- `Catalog/Presentation/Controller/` — TechStack controllers

### Fichiers modifies

- `Catalog/Application/CommandHandler/ScanProjectHandler.php` — cree des Language + Framework au lieu de TechStack
- `Catalog/Application/EventListener/UpdateTechStackVersionStatusListener.php` → adapte pour Framework
- `Catalog/Application/EventListener/RefreshTechStackStatusOnScanListener.php` → adapte pour Framework
- `Catalog/Infrastructure/Scanner/` — les detecteurs retournent language+framework separes (deja le cas dans ScanResult)
- Migration DB : drop tech_stacks, create languages + frameworks

---

## 3. Frontend — Fichiers impactes

### Nouveaux fichiers

**Types:**
- `catalog/types/language.ts` — interface Language, CreateLanguageInput
- `catalog/types/framework.ts` — interface Framework, CreateFrameworkInput

**Services:**
- `catalog/services/language.service.ts`
- `catalog/services/framework.service.ts`

**Stores:**
- `catalog/stores/language.ts` — createCrudStore
- `catalog/stores/framework.ts` — createCrudStore

**Pages:**
- `catalog/pages/LanguageList.vue` — tableau simple : projet, langage, version, status EOL
- `catalog/pages/FrameworkList.vue` — tableau riche : projet, langage, framework, version, LTS, gap, status, health score, provider aggregates, export CSV/PDF

**Composables:**
- `catalog/composables/useFrameworkGrouping.ts` — reprend la logique de useTechStackGrouping pour frameworks
- `catalog/composables/useLanguageFiltering.ts` — filtrage/tri pour langages

### Fichiers supprimes

- `catalog/pages/TechStackList.vue`
- `catalog/pages/TechStackForm.vue`
- `catalog/components/TechStackTable.vue`
- `catalog/components/TechStackFilters.vue`
- `catalog/composables/useTechStackGrouping.ts`
- `catalog/types/tech-stack.ts`
- `catalog/services/tech-stack.service.ts`
- `catalog/stores/tech-stack.ts`

### Fichiers modifies

- `catalog/routes.ts` — remplacer routes tech-stacks par /languages et /frameworks
- `catalog/components/ProjectTechStacksTab.vue` → `ProjectLanguagesTab.vue` + `ProjectFrameworksTab.vue` (ou un composant qui affiche les 2)
- Navigation sidebar — 2 entrees sous Gouvernance : "Langages" et "Frameworks"
- `shared/i18n/locales/en.json` + `fr.json` — nouvelles cles de traduction

---

## 4. Migration DB

```sql
DROP TABLE IF EXISTS catalog_tech_stacks;

CREATE TABLE catalog_languages (
    id UUID PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    version VARCHAR(50) NOT NULL,
    detected_at TIMESTAMP NOT NULL,
    eol_date TIMESTAMP DEFAULT NULL,
    maintenance_status VARCHAR(20) DEFAULT NULL,
    project_id UUID NOT NULL REFERENCES catalog_projects(id) ON DELETE CASCADE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE TABLE catalog_frameworks (
    id UUID PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    version VARCHAR(50) NOT NULL,
    detected_at TIMESTAMP NOT NULL,
    latest_lts VARCHAR(50) DEFAULT NULL,
    lts_gap VARCHAR(100) DEFAULT NULL,
    maintenance_status VARCHAR(20) DEFAULT NULL,
    eol_date TIMESTAMP DEFAULT NULL,
    version_synced_at TIMESTAMP DEFAULT NULL,
    language_id UUID NOT NULL REFERENCES catalog_languages(id) ON DELETE CASCADE,
    project_id UUID NOT NULL REFERENCES catalog_projects(id) ON DELETE CASCADE,
    created_at TIMESTAMP NOT NULL,
    updated_at TIMESTAMP NOT NULL
);

CREATE INDEX idx_languages_project ON catalog_languages(project_id);
CREATE INDEX idx_frameworks_project ON catalog_frameworks(project_id);
CREATE INDEX idx_frameworks_language ON catalog_frameworks(language_id);
```

---

## 5. Navigation

Menu Gouvernance (sidebar) :
```
Gouvernance
├── Langages        → /catalog/languages
├── Frameworks      → /catalog/frameworks
├── Dependances     → /dependency/dependencies
└── Vulnerabilites  → /dependency/vulnerabilities
```

---

## 6. Tests

- Tests unitaires pour Language + Framework (entities, handlers, mappers)
- Tests unitaires pour FrameworkVersionStatusUpdater (renomme)
- Tests frontend pour LanguageList + FrameworkList
- Tests d'integration pour les nouveaux repositories
- Supprimer tous les tests TechStack existants
