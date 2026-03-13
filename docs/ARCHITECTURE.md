# Architecture

## Stack

| Layer | Technology |
|---|---|
| Backend | Symfony 8.0 (PHP 8.4) |
| Frontend | Vue.js 3 + TypeScript + Tailwind CSS 4 |
| Database | PostgreSQL 17 |
| Messaging | RabbitMQ 4 (Symfony Messenger) |
| Containers | Docker Compose |

## DDD Structure

The backend follows Domain-Driven Design with CQRS:

```
src/
  {BoundedContext}/
    Domain/
      Entity/          # Aggregates and entities
      Port/            # Interfaces for external dependencies
      ValueObject/     # Immutable value objects
    Application/
      Command/         # Write operations (CommandHandler)
      Query/           # Read operations (QueryHandler)
      DTO/             # Data Transfer Objects
    Infrastructure/
      Adapter/         # Port implementations
      Repository/      # Doctrine repositories
    Presentation/
      Controller/      # HTTP controllers
```

## Bounded Contexts

| Context | Responsibility |
|---|---|
| **Identity** | Authentication, user profiles, teams |
| **Catalog** | Projects, tech stacks, merge requests |
| **Dependency** | Dependencies, LTS versions, CVE vulnerabilities |
| **Assessment** | Quizzes, questions, attempts and grading |
| **Activity** | Developer dashboard, activity log, notifications |

## CQRS Buses

- **Command Bus**: Synchronous — handles write operations
- **Query Bus**: Synchronous — handles read operations
- **Event Bus**: Asynchronous via RabbitMQ — handles domain events

## Communication between contexts

Contexts communicate via domain events dispatched through Symfony Messenger. Direct dependencies between contexts are forbidden (enforced by Deptrac).
