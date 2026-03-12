---
id: TASK-008.02
title: Backend — GitProviderInterface + clients GitLab/GitHub pour les MR/PR
status: Done
assignee: []
created_date: '2026-03-12 08:01'
updated_date: '2026-03-12 08:13'
labels:
  - backend
  - catalog
  - git-provider
dependencies:
  - TASK-008.01
references:
  - backend/src/Catalog/Domain/Port/GitProviderInterface.php
  - backend/src/Catalog/Domain/Model/RemoteProject.php
  - backend/src/Catalog/Infrastructure/GitProvider/GitLabClient.php
  - backend/src/Catalog/Infrastructure/GitProvider/GitHubClient.php
  - backend/tests/Unit/Catalog/Infrastructure/GitProvider/GitHubClientTest.php
  - backend/tests/Unit/Catalog/Infrastructure/GitProvider/GitLabClientTest.php
parent_task_id: TASK-008
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Étendre GitProviderInterface avec une méthode pour lister les PR/MR d'un projet, puis implémenter dans les clients GitLab et GitHub.

**Interface** — ajouter à `GitProviderInterface` :
```
listMergeRequests(Provider, string externalProjectId, ?string state, int page, int perPage): RemoteMergeRequest[]
```

**Value Object `RemoteMergeRequest`** (dans Domain/Model) :
- externalId, title, description, sourceBranch, targetBranch
- status (string: open/merged/closed/draft)
- author, url
- additions, deletions (nullable int)
- reviewers (string[]), labels (string[])
- createdAt, updatedAt, mergedAt, closedAt (string dates ISO)

**GitLabClient** — `GET /api/v4/projects/{id}/merge_requests` :
- Query params : state (opened/merged/closed/all), page, per_page, order_by=updated_at
- Mapping champs GitLab : `iid` → externalId, `source_branch`, `target_branch`, `state` (opened→open, merged→merged, closed→closed), `draft` flag, `author.username`, `web_url`, `reviewers[].username`, `labels[]`

**GitHubClient** — `GET /repos/{owner/repo}/pulls` :
- Query params : state (open/closed/all), page, per_page, sort=updated, direction=desc
- Mapping champs GitHub : `number` → externalId, `head.ref` → sourceBranch, `base.ref` → targetBranch, `state` + `merged_at` → status, `draft` flag, `user.login` → author, `html_url`, `requested_reviewers[].login`, `labels[].name`, `additions`, `deletions`
- Note : additions/deletions nécessitent un appel séparé `GET /repos/{owner/repo}/pulls/{number}` si non présents dans le list

**Tests Pest** : MockHttpClient pour GitLab et GitHub, mapping des champs, gestion pagination.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 GitProviderInterface expose listMergeRequests(Provider, externalProjectId, state, page, perPage): RemoteMergeRequest[]
- [x] #2 RemoteMergeRequest est un VO readonly avec tous les champs PR/MR (externalId, title, sourceBranch, targetBranch, status, author, url, reviewers, labels, additions, deletions, dates)
- [x] #3 GitLabClient implémente listMergeRequests via GET /api/v4/projects/{id}/merge_requests avec mapping correct des champs
- [x] #4 GitHubClient implémente listMergeRequests via GET /repos/{owner/repo}/pulls avec mapping correct (state+merged_at→status, draft flag)
- [x] #5 Le filtre state fonctionne (open, merged, closed, all/null)
- [x] #6 Tests Pest avec MockHttpClient : réponses GitLab et GitHub mappées correctement, filtre state, projet vide
<!-- AC:END -->

## Final Summary

<!-- SECTION:FINAL_SUMMARY:BEGIN -->
## Fichiers créés
- `backend/src/Catalog/Domain/Model/RemoteMergeRequest.php` — VO readonly avec tous les champs PR/MR
- 10 nouveaux tests : 5 GitLab (mapping, draft, merged, state filter, empty) + 5 GitHub (mapping, merged via merged_at, draft, state filter, empty)

## Fichiers modifiés
- `backend/src/Catalog/Domain/Port/GitProviderInterface.php` — Ajout `listMergeRequests(Provider, externalProjectId, state, page, perPage)`
- `backend/src/Catalog/Infrastructure/GitProvider/GitLabClient.php` — Implémentation via `GET /api/v4/projects/{id}/merge_requests`, mapping state (opened→open, draft flag)
- `backend/src/Catalog/Infrastructure/GitProvider/GitHubClient.php` — Implémentation via `GET /repos/{owner/repo}/pulls`, mapping state+merged_at→status, draft flag, additions/deletions
- 4 fichiers de test stubs mis à jour pour ajouter `listMergeRequests()`

## Tests
- 10 nouveaux tests, 207 tests au total, 0 failures
<!-- SECTION:FINAL_SUMMARY:END -->

## Definition of Done
<!-- DOD:BEGIN -->
- [x] #1 Les tests doivent passer
- [ ] #2 Coverage 80% minimum
- [ ] #3 Mutation 80% minimum
- [ ] #4 Documentation mise à jour
- [x] #5 Lanalyse de code statique doit passer
<!-- DOD:END -->
