---
status: in_progress
step: 6
skill: new-project
---

# Scaffold — Monark

## Choix validés

- Type: web
- Backend: symfony
- Frontend: vue
- BDD: postgresql
- Profil: advanced
- Complexité: advanced
- Tests PHP: pest
- Package manager: pnpm
- SSR: null
- DDD frontend: null
- Layout: dashboard
- UI Framework: tailwind
- Modules: auth, messenger
- Contexts: Identity, Catalog, Dependency, Assessment, Activity

## Features

### Identity
- Authentification (register, login, logout)
- Gestion du profil (view, update, avatar)
- Gestion des équipes (CRUD, membres)

Entités:
- User: email(string), password(string), firstName(string), lastName(string), avatar(string), roles(json) ← AccessToken[] (OneToMany)
- AccessToken: provider(enum:gitlab,github), token(string), scopes(json), expiresAt(datetime) → User (ManyToOne)
- Team: name(string), slug(string), description(text) ↔ User (ManyToMany)

### Catalog
- Gestion des projets (CRUD, lien repo)
- Détection des tech stacks (language, framework, version)
- Monitoring pipelines CI/CD (statuts, historique)

Entités:
- Project: name(string), slug(string), description(text), repositoryUrl(string), defaultBranch(string), visibility(enum:public,private), ownerId(string) ← TechStack[] (OneToMany), ← Pipeline[] (OneToMany)
- TechStack: language(string), framework(string), version(string), detectedAt(datetime) → Project (ManyToOne)
- Pipeline: externalId(string), ref(string), status(enum:pending,running,success,failed), duration(int), startedAt(datetime), finishedAt(datetime) → Project (ManyToOne)

### Dependency
- Analyse des dépendances (scan, inventaire par projet)
- Suivi LTS & détection outdated (comparaison versions)
- Scan vulnérabilités CVE (détection, sévérité, statut)

Entités:
- Dependency: name(string), currentVersion(string), latestVersion(string), ltsVersion(string), packageManager(enum:composer,npm,pip), type(enum:runtime,dev), isOutdated(bool), projectId(string) ← Vulnerability[] (OneToMany)
- Vulnerability: cveId(string), severity(enum:critical,high,medium,low), title(string), description(text), patchedVersion(string), status(enum:open,acknowledged,fixed,ignored), detectedAt(datetime) → Dependency (ManyToOne)

### Assessment
- Gestion des quiz (CRUD, publication, planning)
- Gestion des questions (multi-types, scoring, niveaux)
- Passage de quiz (tentatives, résultats, progression)

Entités:
- Quiz: title(string), slug(string), description(text), type(enum:quiz,survey), status(enum:draft,published,archived), startsAt(datetime), endsAt(datetime), timeLimit(int), authorId(string) ← Question[] (OneToMany)
- Question: type(enum:single_choice,multiple_choice,text,code), content(text), level(enum:easy,medium,hard), score(int), position(int) → Quiz (ManyToOne), ← Answer[] (OneToMany)
- Answer: content(text), isCorrect(bool), position(int) → Question (ManyToOne)
- Attempt: score(int), status(enum:started,submitted,graded), startedAt(datetime), finishedAt(datetime), userId(string), quizId(string)

### Activity
- Dashboard développeur (stats, widgets)
- Journal d'activité (événements, historique)
- Notifications (in-app, préférences)

Entités:
- ActivityEvent: type(string), entityType(string), entityId(string), payload(json), occurredAt(datetime), userId(string)
- Notification: title(string), message(text), channel(enum:in_app,email), readAt(datetime), userId(string)

## Config overrides

Aucune surcharge (profil advanced par défaut)

## Progression

- [x] Étape 1 — Type et stack
- [x] Étape 2 — Profil et complexité
- [x] Étape 3 — Architecture
- [x] Étape 4 — Features, modules et thème
- [x] Étape 5 — Plan validé
- [ ] Étape 6 — Scaffold (structure + config + modules + thème + sécurité + a11y + communs)
- [ ] Étape 7 — Vérification scaffold
- [ ] Étape 8 — Features (0/15)
- [ ] Étape 9 — Vérification finale
- [ ] Étape 10 — Récapitulatif + métriques
