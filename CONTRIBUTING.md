# Contributing to Monark

## Prerequisites

- Docker and Docker Compose
- Make
- Git

## Installation

```bash
make doctor    # Check prerequisites
make install   # Build and start the project
```

## Development workflow

1. Create a feature branch from `main`
2. Make your changes
3. Run quality checks: `make quality`
4. Commit using conventional commits: `<type>(<scope>): <subject>`
5. Push and open a pull request

## Useful commands

| Command | Description |
|---|---|
| `make up` | Start all containers |
| `make down` | Stop all containers |
| `make logs` | Tail container logs |
| `make test` | Run all tests |
| `make lint` | Lint all code |
| `make quality` | Full quality check |
| `make migration` | Generate a migration |
| `make migrate` | Run migrations |
| `make seed` | Load fixtures |
| `make shell-backend` | Shell into backend |
| `make shell-frontend` | Shell into frontend |
| `make help` | Show all commands |

## Commit message format

```
<type>(<scope>): <subject>
```

**Types**: feat, fix, docs, style, refactor, perf, test, build, ci, chore, revert

**Scopes**: identity, catalog, dependency, assessment, activity, docker, ci

## Code standards

- PHP: PHP-CS-Fixer + PHPStan
- TypeScript: ESLint + Prettier
- Tests: Pest (backend), Vitest (frontend)
- Minimum coverage: 80%
