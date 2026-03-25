---
id: TASK-088
title: Adaptateurs registres — récupérer les versions depuis npm et Packagist
status: Done
assignee: []
created_date: '2026-03-18 22:35'
updated_date: '2026-03-18 22:57'
labels:
  - feature
  - dependency
  - infrastructure
dependencies: []
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Créer des adaptateurs (ports + implémentations) pour interroger les registres de packages et récupérer les versions disponibles avec leurs dates de release.

## Registres à supporter
- **npm registry** : `https://registry.npmjs.org/{package}` → champ `time` avec les dates par version
- **Packagist** : `https://repo.packagist.org/p2/{vendor}/{package}.json` → champ `version` + `time`

## Interface
```php
interface PackageRegistryPort {
    /** @return list<RegistryVersion> */
    public function fetchVersions(string $packageName, PackageManager $manager, ?string $sinceVersion = null): array;
}
```

`RegistryVersion` DTO : `version`, `releaseDate`, `isLts` (si détectable)

## Comportement
- `sinceVersion` permet la sync incrémentale : ne retourner que les versions plus récentes
- Gestion des erreurs (404 = package inconnu, rate limit, etc.)
- Cache HTTP pour éviter les appels répétés
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 L'adaptateur npm récupère les versions avec dates de release
- [x] #2 L'adaptateur Packagist récupère les versions avec dates de release
- [x] #3 La sync incrémentale ne charge que les nouvelles versions
- [x] #4 Les erreurs API sont gérées sans crash
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
**Port** : `PackageRegistryPort` interface avec `supports()` et `fetchVersions()`
**DTO** : `RegistryVersion` (version, releaseDate, isLatest)

**Adaptateurs** :
- `NpmRegistryAdapter` : fetch `registry.npmjs.org/{package}`, parse le champ `time`, identifie la `latest` via `dist-tags`
- `PackagistRegistryAdapter` : fetch `repo.packagist.org/p2/{vendor}/{package}.json`, parse le tableau `packages`, filtre dev/beta/alpha/RC

**Factory** : `PackageRegistryFactory` avec `!tagged_iterator` pour router vers le bon adaptateur selon le `PackageManager`

**Sync incrémentale** : paramètre `sinceVersion` filtre les versions <= à la version connue

**Tests** : 9 tests (4 npm + 5 packagist) couvrant fetch normal, filtrage incremental, erreur API, supports().
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [ ] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [ ] #5 Analyse de code statique doit passer
- [ ] #6 Deptrac doit passer
<!-- DOD:END -->
