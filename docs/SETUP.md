# Setup

## Prerequisites

- Docker >= 24.0
- Docker Compose >= 2.20
- Make
- Git

## Installation

```bash
git clone git@github.com:keyxmare/monark.git
cd monark
make doctor
make install
```

## Environment variables

Copy `docker/.env.example` to `docker/.env` (done automatically by `make install`).

| Variable | Default | Description |
|---|---|---|
| `COMPOSE_PROJECT_NAME` | `monark` | Docker Compose project name |
| `POSTGRES_USER` | `app` | PostgreSQL user |
| `POSTGRES_PASSWORD` | `changeme` | PostgreSQL password |
| `POSTGRES_DB` | `monark` | PostgreSQL database |
| `RABBITMQ_DEFAULT_USER` | `guest` | RabbitMQ user |
| `RABBITMQ_DEFAULT_PASS` | `guest` | RabbitMQ password |

## Services

| Service | URL |
|---|---|
| Backend API | http://localhost:8000 |
| Frontend | http://localhost:3000 |
| RabbitMQ Management | http://localhost:15672 |
| PostgreSQL | localhost:5432 |

## Common issues

**Ports already in use**: Stop conflicting services or change ports in `docker/compose.override.yaml`.

**Permission denied**: Ensure Docker runs without sudo or add your user to the `docker` group.
