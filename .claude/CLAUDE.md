# Monark

Hub développeur — monitoring de projets, dépendances et vulnérabilités.

## Stack

- **Backend**: Symfony 8.0 (PHP 8.4) — DDD/CQRS
- **Frontend**: Vue.js 3 + TypeScript + Tailwind CSS 4
- **Database**: PostgreSQL 17
- **Messaging**: RabbitMQ 4 (Symfony Messenger)
- **Tests**: Pest (PHP), Vitest (TS)
- **Package manager**: pnpm (frontend), Composer (backend)
- **Runtime**: Docker Compose (tout via `make` ou `docker compose exec`)

## Architecture

### Bounded Contexts

| Context | Responsabilité |
|---|---|
| Identity | Authentification, profils |
| Catalog | Projets, tech stacks, merge requests |
| Dependency | Dépendances, versions LTS, vulnérabilités CVE |
| Activity | Dashboard, journal d'activité, notifications |

### Structure backend (DDD)

```
src/
  {Context}/
    Domain/
      Entity/
      Port/
      ValueObject/
    Application/
      Command/
      Query/
      DTO/
    Infrastructure/
      Adapter/
      Repository/
    Presentation/
      Controller/
```

### Structure frontend

```
src/
  modules/
    {context}/
      pages/
      components/
      composables/
      services/
      stores/
      types/
```

## Modules actifs

- **auth**: Authentification JWT
- **messenger**: Bus async RabbitMQ

## Conventions

- Pas de commentaires dans le code
- Commits conventionnels : `<type>(<scope>): <subject>`
- Toute commande via `make` ou `docker compose exec` (jamais en direct)
- Tests obligatoires pour tout code métier
- Coverage minimum : 80%
