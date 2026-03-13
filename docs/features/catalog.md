# Catalog

Project management and tech stack detection.

## Features

### Projects
- CRUD operations for projects
- Link to external repositories (GitLab, GitHub)
- Sync project metadata from provider

### Tech Stacks
- Automatic detection of languages and frameworks
- Version tracking per project
- Historical detection records

## Entities

- **Project**: name, slug, description, repositoryUrl, defaultBranch, visibility, ownerId
- **TechStack**: language, framework, version, detectedAt
