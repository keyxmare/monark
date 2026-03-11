# ADR 0001 — Initial DDD/CQRS Architecture

**Status**: Accepted
**Date**: 2026-03-11

## Context

Monark is a developer hub that manages multiple domains: identity, project catalog, dependency tracking, assessments, and activity monitoring. These domains have distinct business rules and different rates of change.

## Decision

We adopt Domain-Driven Design (DDD) with CQRS for the backend architecture:

- **Bounded Contexts** isolate each domain with clear boundaries
- **CQRS** separates read and write models for flexibility and performance
- **Domain Events** via RabbitMQ enable asynchronous communication between contexts
- **Ports & Adapters** decouple domain logic from infrastructure

## Consequences

**Positive**:
- Each context can evolve independently
- Clear separation of concerns
- Testable domain logic without infrastructure dependencies
- Async processing for heavy operations (scans, syncs)

**Negative**:
- Higher initial complexity compared to a CRUD approach
- More files and boilerplate per feature
- Team must understand DDD patterns

**Mitigations**:
- Deptrac enforces boundaries at CI level
- Scaffolding tools reduce boilerplate effort
- Documentation and ADRs record decisions
