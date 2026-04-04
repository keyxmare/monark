# Monark — Instructions projet

## Stack

- **Backend** : Symfony 8 / PHP 8.4 / Pest / PHPStan
- **Frontend** : Vue.js 3 / TypeScript / Vitest / Pinia
- **Infra** : Docker (PostgreSQL 17, RabbitMQ, Mercure, Redis)
- **Architecture** : DDD/CQRS, bounded contexts (Identity, Catalog, Dependency, Sync, Coverage, VersionRegistry)

## Runtime

Tout passe par Docker — ne jamais exécuter php/composer/pnpm directement.

```
make test-backend       # Pest tests
make test-frontend      # Vitest tests
make lint-backend       # cs-fixer (dry-run) + PHPStan
make lint-frontend      # ESLint + Prettier
make fix-backend        # cs-fixer auto-fix
make ci                 # Dashboard complet
make migrate            # Doctrine migrations
```

## Workflow feature

1. Lire le code existant, comprendre les patterns
2. Implémenter
3. Si interface PHP modifiée (ajout méthode, arg constructeur) → grepper `implements NomInterface` et `new NomHandler(` dans `backend/tests/` pour mettre à jour tous les stubs
4. Vérification (voir ci-dessous)
5. Commit

## Workflow debug

1. Observer l'état réel : `docker logs`, query DB (`psql`), queue RabbitMQ (`rabbitmqctl list_queues`), logs Mercure
2. Identifier la root cause — vérifier l'infra avant de soupçonner le code
3. Fix ciblé sur la cause, pas le symptôme
4. Les publications Mercure doivent être dans un try/catch — ne jamais casser une transaction DB
5. Vérification (voir ci-dessous)

## Vérification post-implémentation

Obligatoire après chaque feature ou fix, AVANT commit. S'arrêter à la première erreur, corriger, recommencer.

```
make fix-backend                    # 1. Auto-fix cs-fixer
make lint-backend                   # 2. cs-fixer dry-run + PHPStan → 0 errors
make test-backend                   # 3. Pest → 0 failures, 0 errors
make lint-frontend                  # 4. ESLint + Prettier → 0 errors
make test-frontend                  # 5. Vitest → 0 failures
```

Si une step échoue :
- **cs-fixer** : `make fix-backend` a déjà corrigé, relancer `make lint-backend`
- **PHPStan** : corriger les types (`@var`, `@param`, casts), pas de `@phpstan-ignore`
- **Pest** : lire l'erreur, corriger le test OU le code, relancer
- **ESLint/Prettier** : `pnpm format` via Docker, relancer
- **Vitest** : lire l'erreur, corriger, relancer

Ne jamais commit avec des tests rouge ou du lint KO.

## Workflow suppression de feature

1. Inventaire exhaustif avec Agent(Explore) : entities, repos, commands, handlers, controllers, DTOs, events, tests, frontend (types, services, stores, pages, components), config, routes, i18n
2. `git rm` les fichiers dédiés
3. Éditer les fichiers partagés (config, interfaces, entités, tests)
4. Migration pour dropper les tables
5. Grep final pour vérifier 0 référence restante

## Conventions code

- Pas de commentaires dans le code
- Les commandes d'orchestration (coordinateurs de workflow) sont **synchrones** — seules les commandes de travail (scan, fetch, sync) vont dans la queue async RabbitMQ
- Chaque endpoint a son controller invokable (`__invoke`)
- Les interfaces DDD sont dans `Domain/Port/`, les implémentations dans `Infrastructure/`
- Les tests utilisent des anonymous classes pour implémenter les interfaces (pas de mocks Mockery sauf pour PHPUnit)

## Pièges connus

- **PHPStan** : `memory_limit=-1` dans le Dockerfile, sinon crash silencieux
- **Consumer RabbitMQ** : single-thread, un message lent bloque toute la queue
- **Pest output** : avec `exec -T` (no TTY), pest écrit sur stderr — utiliser `--log-junit` pour parser les résultats
- **BusyBox grep** : le container Alpine n'a pas `grep -P`, utiliser `grep -oE` à la place
