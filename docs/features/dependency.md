# Dependency

Dependency analysis, LTS tracking, and CVE vulnerability scanning.

## Features

### Dependency Analysis
- Scan project dependencies (Composer, npm, pip)
- Inventory per project with current/latest versions
- Runtime vs dev dependency classification

### LTS & Outdated Detection
- Compare current version against latest and LTS
- Flag outdated dependencies
- Track upgrade paths

### CVE Scanning
- Detect known vulnerabilities
- Severity classification (critical, high, medium, low)
- Status tracking (open, acknowledged, fixed, ignored)
- Patched version recommendations

## Entities

- **Dependency**: name, currentVersion, latestVersion, ltsVersion, packageManager, type, isOutdated
- **Vulnerability**: cveId, severity, title, description, patchedVersion, status, detectedAt
