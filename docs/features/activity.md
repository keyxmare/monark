# Activity

Developer dashboard, activity journal, and notifications.

## Features

### Dashboard
- Aggregated stats (projects, dependencies, vulnerabilities)
- Configurable widgets
- Per-user personalization

### Activity Journal
- Chronological event log
- Filter by entity type, user, date range
- Event payload for audit trail

### Notifications
- In-app notifications
- Email notifications (optional)
- Read/unread status management
- User notification preferences

## Entities

- **ActivityEvent**: type, entityType, entityId, payload, occurredAt, userId
- **Notification**: title, message, channel (in_app/email), readAt, userId
