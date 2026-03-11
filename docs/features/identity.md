# Identity

Authentication, user profiles, and team management.

## Features

### Authentication
- User registration with email validation
- Login / logout with JWT tokens
- Access token management (GitLab, GitHub)

### Profile
- View and update profile (name, avatar)
- Manage connected providers

### Teams
- Create and manage teams
- Add/remove team members
- Team-scoped project access

## Entities

- **User**: email, password, firstName, lastName, avatar, roles
- **AccessToken**: provider (gitlab/github), token, scopes, expiresAt
- **Team**: name, slug, description
