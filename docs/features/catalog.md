# Catalog

Project management, tech stack detection, and CI/CD pipeline monitoring.

## Features

### Projects
- CRUD operations for projects
- Link to external repositories (GitLab, GitHub)
- Sync project metadata from provider

### Tech Stacks
- Automatic detection of languages and frameworks
- Version tracking per project
- Historical detection records

### Pipelines
- Monitor CI/CD pipeline status
- Track duration and history
- Alert on failures

## Entities

- **Project**: name, slug, description, repositoryUrl, defaultBranch, visibility, ownerId
- **TechStack**: language, framework, version, detectedAt
- **Pipeline**: externalId, ref, status (pending/running/success/failed), duration, startedAt, finishedAt
