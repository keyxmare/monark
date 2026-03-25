# Identity

Authentication and user profiles.

## Features

### Authentication
- User registration with email validation
- Login / logout with JWT tokens
- Access token management (GitLab, GitHub)

### Profile
- View and update profile (name, avatar)
- Manage connected providers

## Entities

- **User**: email, password, firstName, lastName, avatar, roles
- **AccessToken**: provider (gitlab/github), token, scopes, expiresAt
