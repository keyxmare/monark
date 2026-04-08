# Phase 1 — Backend Integration & Functional Tests

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fill the empty Integration and Functional test suites to validate repositories against a real DB, event listener chains, and all 64 API endpoints end-to-end.

**Architecture:** Integration tests use `KernelTestCase` (Symfony container + real PostgreSQL). Functional tests use `WebTestCase` (full HTTP kernel). A shared `DatabaseHelper` trait resets the DB between tests via schema drop/create + migrations. An `AuthHelper` trait creates an authenticated user and generates a bearer token.

**Tech Stack:** Pest 4, Symfony 8 WebTestCase/KernelTestCase, Doctrine ORM, PostgreSQL 17 (test DB).

**Docker commands:** All test commands must run via Docker:
- `docker compose exec php bin/console ...` for console commands
- `docker compose exec php vendor/bin/pest ...` for running tests

---

## File Structure

### New files to create:
```
tests/
├── Helpers/
│   ├── DatabaseHelper.php          — Trait: reset DB schema between tests
│   └── AuthHelper.php              — Trait: create user + generate bearer token
├── Integration/
│   ├── Identity/
│   │   ├── DoctrineUserRepositoryTest.php
│   │   └── DoctrineAccessTokenRepositoryTest.php
│   ├── Catalog/
│   │   ├── DoctrineProjectRepositoryTest.php
│   │   ├── DoctrineProviderRepositoryTest.php
│   │   ├── DoctrineTechStackRepositoryTest.php
│   │   ├── DoctrineMergeRequestRepositoryTest.php
│   │   └── DoctrineSyncJobRepositoryTest.php
│   ├── Dependency/
│   │   ├── DoctrineDependencyRepositoryTest.php
│   │   ├── DoctrineDependencyVersionRepositoryTest.php
│   │   └── DoctrineVulnerabilityRepositoryTest.php
│   └── Activity/
│       ├── DoctrineActivityEventRepositoryTest.php
│       ├── DoctrineNotificationRepositoryTest.php
│       ├── DoctrineBuildMetricRepositoryTest.php
│       └── DoctrineSyncTaskRepositoryTest.php
├── Functional/
│   ├── Identity/
│   │   ├── AuthEndpointsTest.php       — register, login, logout, profile
│   │   ├── UserEndpointsTest.php       — list, get, update users
│   │   └── AccessTokenEndpointsTest.php — CRUD access tokens
│   ├── Catalog/
│   │   ├── ProjectEndpointsTest.php    — CRUD + scan projects
│   │   ├── ProviderEndpointsTest.php   — CRUD + test connection + remote projects + import
│   │   ├── TechStackEndpointsTest.php  — CRUD tech stacks
│   │   ├── MergeRequestEndpointsTest.php — list, get MRs
│   │   └── SyncEndpointsTest.php       — sync-all, sync-job
│   ├── Dependency/
│   │   ├── DependencyEndpointsTest.php — CRUD + stats + sync
│   │   └── VulnerabilityEndpointsTest.php — CRUD vulns
│   ├── Activity/
│   │   ├── ActivityEventEndpointsTest.php
│   │   ├── NotificationEndpointsTest.php
│   │   ├── SyncTaskEndpointsTest.php
│   │   ├── BuildMetricEndpointsTest.php
│   │   └── DashboardEndpointsTest.php
│   └── Shared/
│       └── HealthEndpointsTest.php     — healthz, readyz
```

### Files to modify:
```
tests/Pest.php                      — remove is_dir guards (dirs now exist)
backend/Makefile                    — add targets for integration/functional suites
```

---

### Task 1: Test Infrastructure — Database & Auth Helpers

**Files:**
- Create: `backend/tests/Helpers/DatabaseHelper.php`
- Create: `backend/tests/Helpers/AuthHelper.php`
- Modify: `backend/tests/Pest.php`

- [ ] **Step 1: Create DatabaseHelper trait**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Helpers;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

trait DatabaseHelper
{
    private static bool $schemaCreated = false;

    protected function resetDatabase(): void
    {
        $em = $this->getEntityManager();
        $metadata = $em->getMetadataFactory()->getAllMetadata();

        $schemaTool = new SchemaTool($em);

        if (! self::$schemaCreated) {
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
            self::$schemaCreated = true;
        } else {
            $connection = $em->getConnection();
            $connection->executeStatement('SET session_replication_role = replica');

            foreach ($metadata as $classMetadata) {
                $connection->executeStatement(
                    'TRUNCATE TABLE ' . $classMetadata->getTableName() . ' CASCADE'
                );
            }

            $connection->executeStatement('SET session_replication_role = DEFAULT');
        }

        $em->clear();
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }
}
```

- [ ] **Step 2: Create AuthHelper trait**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Helpers;

use App\Identity\Domain\Model\User;
use App\Identity\Infrastructure\Security\ApiTokenHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

trait AuthHelper
{
    protected function createAuthenticatedUser(array $overrides = []): array
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $hasher = $container->get(UserPasswordHasherInterface::class);
        $tokenHandler = $container->get(ApiTokenHandler::class);

        $user = User::create(
            email: $overrides['email'] ?? 'test@example.com',
            hashedPassword: 'temp',
            firstName: $overrides['firstName'] ?? 'Test',
            lastName: $overrides['lastName'] ?? 'User',
            roles: $overrides['roles'] ?? ['ROLE_USER'],
        );

        $hashedPassword = $hasher->hashPassword($user, $overrides['password'] ?? 'password123');
        $user->updatePassword($hashedPassword);

        $em->persist($user);
        $em->flush();

        $token = $tokenHandler->createToken($user->getId()->toRfc4122());

        return ['user' => $user, 'token' => $token];
    }

    protected function authHeader(string $token): array
    {
        return ['HTTP_AUTHORIZATION' => 'Bearer ' . $token];
    }
}
```

- [ ] **Step 3: Update Pest.php — remove is_dir guards**

Replace the contents of `backend/tests/Pest.php` with:

```php
<?php

declare(strict_types=1);

pest()
    ->extends(\PHPUnit\Framework\TestCase::class)
    ->in('Unit');

pest()
    ->extends(\Symfony\Bundle\FrameworkBundle\Test\KernelTestCase::class)
    ->in('Integration');

pest()
    ->extends(\Symfony\Bundle\FrameworkBundle\Test\WebTestCase::class)
    ->in('Functional');
```

- [ ] **Step 4: Remove .gitkeep files from Integration/ and Functional/**

Run:
```bash
docker compose exec php rm -f tests/Integration/.gitkeep tests/Functional/.gitkeep
```

- [ ] **Step 5: Verify the test infrastructure boots**

Create a smoke test file `backend/tests/Integration/SmokeTest.php`:

```php
<?php

declare(strict_types=1);

use App\Tests\Helpers\DatabaseHelper;
use Doctrine\ORM\EntityManagerInterface;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
});

it('can boot kernel and access entity manager', function () {
    $em = $this->getEntityManager();

    expect($em)->toBeInstanceOf(EntityManagerInterface::class);
});
```

Run:
```bash
docker compose exec php vendor/bin/pest tests/Integration/SmokeTest.php --verbose
```
Expected: 1 test passes.

- [ ] **Step 6: Commit**

```bash
git add backend/tests/Helpers/ backend/tests/Integration/SmokeTest.php backend/tests/Pest.php
git commit -m "test: add test infrastructure — DatabaseHelper, AuthHelper traits and integration smoke test"
```

---

### Task 2: Integration Tests — Identity Repositories

**Files:**
- Create: `backend/tests/Integration/Identity/DoctrineUserRepositoryTest.php`
- Create: `backend/tests/Integration/Identity/DoctrineAccessTokenRepositoryTest.php`
- Delete: `backend/tests/Integration/SmokeTest.php` (served its purpose)

- [ ] **Step 1: Write DoctrineUserRepositoryTest**

```php
<?php

declare(strict_types=1);

use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Tests\Helpers\DatabaseHelper;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(UserRepositoryInterface::class);
});

describe('DoctrineUserRepository', function () {
    it('saves and finds a user by id', function () {
        $user = User::create('alice@test.com', 'hashed', 'Alice', 'Smith');
        $this->repo->save($user);

        $found = $this->repo->findById($user->getId());

        expect($found)->not->toBeNull();
        expect($found->getEmail())->toBe('alice@test.com');
        expect($found->getFirstName())->toBe('Alice');
    });

    it('finds a user by email', function () {
        $user = User::create('bob@test.com', 'hashed', 'Bob', 'Jones');
        $this->repo->save($user);

        $found = $this->repo->findByEmail('bob@test.com');

        expect($found)->not->toBeNull();
        expect($found->getId()->equals($user->getId()))->toBeTrue();
    });

    it('returns null for unknown email', function () {
        expect($this->repo->findByEmail('unknown@test.com'))->toBeNull();
    });

    it('returns null for unknown id', function () {
        expect($this->repo->findById(\Symfony\Component\Uid\Uuid::v7()))->toBeNull();
    });

    it('lists users with pagination', function () {
        for ($i = 0; $i < 5; $i++) {
            $this->repo->save(User::create("user{$i}@test.com", 'hashed', "User{$i}", 'Test'));
        }

        $page1 = $this->repo->findAll(page: 1, perPage: 2);
        $page2 = $this->repo->findAll(page: 2, perPage: 2);

        expect($page1)->toHaveCount(2);
        expect($page2)->toHaveCount(2);
    });

    it('counts users', function () {
        expect($this->repo->count())->toBe(0);

        $this->repo->save(User::create('a@test.com', 'h', 'A', 'B'));
        $this->repo->save(User::create('b@test.com', 'h', 'C', 'D'));

        expect($this->repo->count())->toBe(2);
    });

    it('persists updated user fields', function () {
        $user = User::create('orig@test.com', 'hashed', 'Orig', 'Name');
        $this->repo->save($user);

        $user->update(firstName: 'Updated');
        $this->repo->save($user);

        $this->getEntityManager()->clear();
        $found = $this->repo->findById($user->getId());

        expect($found->getFirstName())->toBe('Updated');
    });
});
```

- [ ] **Step 2: Run to verify it passes**

```bash
docker compose exec php vendor/bin/pest tests/Integration/Identity/DoctrineUserRepositoryTest.php --verbose
```
Expected: 6 tests pass.

- [ ] **Step 3: Write DoctrineAccessTokenRepositoryTest**

```php
<?php

declare(strict_types=1);

use App\Identity\Domain\Model\AccessToken;
use App\Identity\Domain\Model\TokenProvider;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\AccessTokenRepositoryInterface;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Tests\Helpers\DatabaseHelper;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(AccessTokenRepositoryInterface::class);
    $this->userRepo = self::getContainer()->get(UserRepositoryInterface::class);

    $this->user = User::create('owner@test.com', 'hashed', 'Owner', 'User');
    $this->userRepo->save($this->user);
});

describe('DoctrineAccessTokenRepository', function () {
    it('saves and finds a token by id', function () {
        $token = AccessToken::create(TokenProvider::Gitlab, 'glpat-xxx', ['api'], null, $this->user);
        $this->repo->save($token);

        $found = $this->repo->findById($token->getId());

        expect($found)->not->toBeNull();
        expect($found->getToken())->toBe('glpat-xxx');
    });

    it('finds tokens by user with pagination', function () {
        for ($i = 0; $i < 3; $i++) {
            $this->repo->save(
                AccessToken::create(TokenProvider::Gitlab, "token-{$i}", ['api'], null, $this->user)
            );
        }

        $tokens = $this->repo->findByUser($this->user->getId(), page: 1, perPage: 2);

        expect($tokens)->toHaveCount(2);
    });

    it('counts tokens by user', function () {
        $this->repo->save(AccessToken::create(TokenProvider::Gitlab, 'tok1', ['api'], null, $this->user));
        $this->repo->save(AccessToken::create(TokenProvider::Github, 'tok2', ['repo'], null, $this->user));

        expect($this->repo->countByUser($this->user->getId()))->toBe(2);
    });

    it('deletes a token', function () {
        $token = AccessToken::create(TokenProvider::Gitlab, 'to-delete', ['api'], null, $this->user);
        $this->repo->save($token);

        $this->repo->delete($token);
        $this->getEntityManager()->clear();

        expect($this->repo->findById($token->getId()))->toBeNull();
    });

    it('returns null for unknown id', function () {
        expect($this->repo->findById(\Symfony\Component\Uid\Uuid::v7()))->toBeNull();
    });
});
```

- [ ] **Step 4: Run to verify**

```bash
docker compose exec php vendor/bin/pest tests/Integration/Identity/ --verbose
```
Expected: 11 tests pass.

- [ ] **Step 5: Delete smoke test and commit**

```bash
rm backend/tests/Integration/SmokeTest.php
git add backend/tests/Integration/Identity/ -f
git rm backend/tests/Integration/.gitkeep 2>/dev/null; true
git commit -m "test(integration): add Identity repository tests — User and AccessToken"
```

---

### Task 3: Integration Tests — Catalog Repositories

**Files:**
- Create: `backend/tests/Integration/Catalog/DoctrineProjectRepositoryTest.php`
- Create: `backend/tests/Integration/Catalog/DoctrineProviderRepositoryTest.php`
- Create: `backend/tests/Integration/Catalog/DoctrineTechStackRepositoryTest.php`
- Create: `backend/tests/Integration/Catalog/DoctrineMergeRequestRepositoryTest.php`
- Create: `backend/tests/Integration/Catalog/DoctrineSyncJobRepositoryTest.php`

- [ ] **Step 1: Write DoctrineProjectRepositoryTest**

```php
<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\ProviderType;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Catalog\Domain\Repository\ProviderRepositoryInterface;
use App\Tests\Helpers\DatabaseHelper;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(ProjectRepositoryInterface::class);
    $this->providerRepo = self::getContainer()->get(ProviderRepositoryInterface::class);
});

describe('DoctrineProjectRepository', function () {
    it('saves and finds a project by id', function () {
        $project = Project::create('Test', 'test', 'desc', 'https://git.com/test', 'main', ProjectVisibility::Private, Uuid::v7());
        $this->repo->save($project);

        $found = $this->repo->findById($project->getId());

        expect($found)->not->toBeNull();
        expect($found->getName())->toBe('Test');
        expect($found->getSlug())->toBe('test');
    });

    it('finds a project by slug', function () {
        $project = Project::create('Slug Test', 'slug-test', null, 'https://git.com/slug', 'main', ProjectVisibility::Public, Uuid::v7());
        $this->repo->save($project);

        $found = $this->repo->findBySlug('slug-test');

        expect($found)->not->toBeNull();
        expect($found->getName())->toBe('Slug Test');
    });

    it('returns null for unknown slug', function () {
        expect($this->repo->findBySlug('nonexistent'))->toBeNull();
    });

    it('lists projects with pagination', function () {
        for ($i = 0; $i < 5; $i++) {
            $this->repo->save(
                Project::create("P{$i}", "p-{$i}", null, "https://git.com/{$i}", 'main', ProjectVisibility::Private, Uuid::v7())
            );
        }

        $page1 = $this->repo->findAll(page: 1, perPage: 3);
        expect($page1)->toHaveCount(3);

        $page2 = $this->repo->findAll(page: 2, perPage: 3);
        expect($page2)->toHaveCount(2);
    });

    it('counts projects', function () {
        expect($this->repo->count())->toBe(0);

        $this->repo->save(Project::create('A', 'a', null, 'https://git.com/a', 'main', ProjectVisibility::Private, Uuid::v7()));
        expect($this->repo->count())->toBe(1);
    });

    it('deletes a project', function () {
        $project = Project::create('Del', 'del', null, 'https://git.com/del', 'main', ProjectVisibility::Private, Uuid::v7());
        $this->repo->save($project);

        $this->repo->delete($project);
        $this->getEntityManager()->clear();

        expect($this->repo->findById($project->getId()))->toBeNull();
    });

    it('finds projects by provider id', function () {
        $provider = Provider::create('GL', ProviderType::GitLab, 'https://gl.com', 'token', null);
        $this->providerRepo->save($provider);

        $project = Project::create('WithProv', 'with-prov', null, 'https://git.com/wp', 'main', ProjectVisibility::Private, Uuid::v7());
        $project->linkToProvider($provider, '123');
        $this->repo->save($project);

        $found = $this->repo->findByProviderId($provider->getId());
        expect($found)->toHaveCount(1);
        expect($found[0]->getSlug())->toBe('with-prov');
    });

    it('finds by external id and provider', function () {
        $provider = Provider::create('GL2', ProviderType::GitLab, 'https://gl2.com', 'token', null);
        $this->providerRepo->save($provider);

        $project = Project::create('Ext', 'ext', null, 'https://git.com/ext', 'main', ProjectVisibility::Private, Uuid::v7());
        $project->linkToProvider($provider, 'ext-456');
        $this->repo->save($project);

        $found = $this->repo->findByExternalIdAndProvider('ext-456', $provider->getId());
        expect($found)->not->toBeNull();
        expect($found->getSlug())->toBe('ext');
    });

    it('builds external id map by provider', function () {
        $provider = Provider::create('GL3', ProviderType::GitLab, 'https://gl3.com', 'token', null);
        $this->providerRepo->save($provider);

        $p1 = Project::create('M1', 'm-1', null, 'https://git.com/m1', 'main', ProjectVisibility::Private, Uuid::v7());
        $p1->linkToProvider($provider, 'ext-1');
        $this->repo->save($p1);

        $p2 = Project::create('M2', 'm-2', null, 'https://git.com/m2', 'main', ProjectVisibility::Private, Uuid::v7());
        $p2->linkToProvider($provider, 'ext-2');
        $this->repo->save($p2);

        $map = $this->repo->findExternalIdMapByProvider($provider->getId());
        expect($map)->toHaveCount(2);
        expect($map)->toHaveKey('ext-1');
        expect($map)->toHaveKey('ext-2');
    });

    it('finds all projects with provider', function () {
        $provider = Provider::create('GL4', ProviderType::GitLab, 'https://gl4.com', 'token', null);
        $this->providerRepo->save($provider);

        $withProvider = Project::create('WP', 'wp', null, 'https://git.com/wp', 'main', ProjectVisibility::Private, Uuid::v7());
        $withProvider->linkToProvider($provider, 'ext-99');
        $this->repo->save($withProvider);

        $without = Project::create('NP', 'np', null, 'https://git.com/np', 'main', ProjectVisibility::Private, Uuid::v7());
        $this->repo->save($without);

        $found = $this->repo->findAllWithProvider();
        expect($found)->toHaveCount(1);
    });
});
```

- [ ] **Step 2: Run to verify**

```bash
docker compose exec php vendor/bin/pest tests/Integration/Catalog/DoctrineProjectRepositoryTest.php --verbose
```
Expected: 10 tests pass.

- [ ] **Step 3: Write DoctrineProviderRepositoryTest**

```php
<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\Provider;
use App\Catalog\Domain\Model\ProviderType;
use App\Catalog\Domain\Repository\ProviderRepositoryInterface;
use App\Tests\Helpers\DatabaseHelper;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(ProviderRepositoryInterface::class);
});

describe('DoctrineProviderRepository', function () {
    it('saves and finds a provider by id', function () {
        $provider = Provider::create('GitLab', ProviderType::GitLab, 'https://gitlab.com', 'token', null);
        $this->repo->save($provider);

        $found = $this->repo->findById($provider->getId());
        expect($found)->not->toBeNull();
        expect($found->getName())->toBe('GitLab');
    });

    it('lists providers with pagination', function () {
        for ($i = 0; $i < 3; $i++) {
            $this->repo->save(Provider::create("Prov{$i}", ProviderType::GitLab, "https://gl{$i}.com", 'tok', null));
        }

        expect($this->repo->findAll(page: 1, perPage: 2))->toHaveCount(2);
    });

    it('counts providers', function () {
        expect($this->repo->count())->toBe(0);
        $this->repo->save(Provider::create('P', ProviderType::GitLab, 'https://p.com', 'tok', null));
        expect($this->repo->count())->toBe(1);
    });

    it('removes a provider', function () {
        $provider = Provider::create('Del', ProviderType::GitLab, 'https://del.com', 'tok', null);
        $this->repo->save($provider);

        $this->repo->remove($provider);
        $this->getEntityManager()->clear();

        expect($this->repo->findById($provider->getId()))->toBeNull();
    });
});
```

- [ ] **Step 4: Write DoctrineTechStackRepositoryTest**

```php
<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Model\TechStack;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Catalog\Domain\Repository\TechStackRepositoryInterface;
use App\Tests\Helpers\DatabaseHelper;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(TechStackRepositoryInterface::class);
    $this->projectRepo = self::getContainer()->get(ProjectRepositoryInterface::class);

    $this->project = Project::create('P', 'p', null, 'https://git.com/p', 'main', ProjectVisibility::Private, Uuid::v7());
    $this->projectRepo->save($this->project);
});

describe('DoctrineTechStackRepository', function () {
    it('saves and finds by id', function () {
        $ts = TechStack::create('PHP', 'Symfony', '8.4', '8.0', new DateTimeImmutable(), $this->project);
        $this->repo->save($ts);

        $found = $this->repo->findById($ts->getId());
        expect($found)->not->toBeNull();
        expect($found->getLanguage())->toBe('PHP');
    });

    it('finds by project id with pagination', function () {
        for ($i = 0; $i < 3; $i++) {
            $this->repo->save(TechStack::create("Lang{$i}", 'FW', '1.0', '1.0', new DateTimeImmutable(), $this->project));
        }

        expect($this->repo->findByProjectId($this->project->getId(), 1, 2))->toHaveCount(2);
    });

    it('counts by project id', function () {
        $this->repo->save(TechStack::create('PHP', 'Symfony', '8.4', '8.0', new DateTimeImmutable(), $this->project));
        expect($this->repo->countByProjectId($this->project->getId()))->toBe(1);
    });

    it('deletes by project id', function () {
        $this->repo->save(TechStack::create('PHP', 'SF', '8.4', '8.0', new DateTimeImmutable(), $this->project));
        $this->repo->save(TechStack::create('JS', 'React', '18', '18', new DateTimeImmutable(), $this->project));

        $this->repo->deleteByProjectId($this->project->getId());
        $this->getEntityManager()->clear();

        expect($this->repo->countByProjectId($this->project->getId()))->toBe(0);
    });

    it('deletes a single tech stack', function () {
        $ts = TechStack::create('Go', 'None', '1.22', '', new DateTimeImmutable(), $this->project);
        $this->repo->save($ts);

        $this->repo->delete($ts);
        $this->getEntityManager()->clear();

        expect($this->repo->findById($ts->getId()))->toBeNull();
    });
});
```

- [ ] **Step 5: Write DoctrineMergeRequestRepositoryTest**

```php
<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\MergeRequest;
use App\Catalog\Domain\Model\MergeRequestStatus;
use App\Catalog\Domain\Model\Project;
use App\Catalog\Domain\Model\ProjectVisibility;
use App\Catalog\Domain\Repository\MergeRequestRepositoryInterface;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Tests\Helpers\DatabaseHelper;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(MergeRequestRepositoryInterface::class);
    $this->projectRepo = self::getContainer()->get(ProjectRepositoryInterface::class);

    $this->project = Project::create('P', 'p', null, 'https://git.com/p', 'main', ProjectVisibility::Private, Uuid::v7());
    $this->projectRepo->save($this->project);
});

describe('DoctrineMergeRequestRepository', function () {
    it('saves and finds by id', function () {
        $mr = MergeRequest::create('ext-1', 'Fix bug', MergeRequestStatus::Open, new DateTimeImmutable(), null, 'Alice', $this->project);
        $this->repo->save($mr);

        $found = $this->repo->findById($mr->getId());
        expect($found)->not->toBeNull();
        expect($found->getTitle())->toBe('Fix bug');
    });

    it('finds by project id with status filter', function () {
        $this->repo->save(MergeRequest::create('e1', 'Open MR', MergeRequestStatus::Open, new DateTimeImmutable(), null, 'Alice', $this->project));
        $this->repo->save(MergeRequest::create('e2', 'Merged MR', MergeRequestStatus::Merged, new DateTimeImmutable(), new DateTimeImmutable(), 'Bob', $this->project));
        $this->repo->save(MergeRequest::create('e3', 'Another Open', MergeRequestStatus::Open, new DateTimeImmutable(), null, 'Charlie', $this->project));

        $openOnly = $this->repo->findByProjectId($this->project->getId(), 1, 20, [MergeRequestStatus::Open]);
        expect($openOnly)->toHaveCount(2);

        $merged = $this->repo->findByProjectId($this->project->getId(), 1, 20, [MergeRequestStatus::Merged]);
        expect($merged)->toHaveCount(1);
    });

    it('filters by author', function () {
        $this->repo->save(MergeRequest::create('e1', 'MR1', MergeRequestStatus::Open, new DateTimeImmutable(), null, 'Alice', $this->project));
        $this->repo->save(MergeRequest::create('e2', 'MR2', MergeRequestStatus::Open, new DateTimeImmutable(), null, 'Bob', $this->project));

        $aliceOnly = $this->repo->findByProjectId($this->project->getId(), 1, 20, [], 'Alice');
        expect($aliceOnly)->toHaveCount(1);
    });

    it('counts by project id with filters', function () {
        $this->repo->save(MergeRequest::create('e1', 'MR1', MergeRequestStatus::Open, new DateTimeImmutable(), null, 'A', $this->project));
        $this->repo->save(MergeRequest::create('e2', 'MR2', MergeRequestStatus::Closed, new DateTimeImmutable(), null, 'B', $this->project));

        expect($this->repo->countByProjectId($this->project->getId()))->toBe(2);
        expect($this->repo->countByProjectId($this->project->getId(), [MergeRequestStatus::Open]))->toBe(1);
    });

    it('finds by external id and project', function () {
        $mr = MergeRequest::create('unique-ext', 'Title', MergeRequestStatus::Open, new DateTimeImmutable(), null, 'A', $this->project);
        $this->repo->save($mr);

        $found = $this->repo->findByExternalIdAndProject('unique-ext', $this->project->getId());
        expect($found)->not->toBeNull();

        expect($this->repo->findByExternalIdAndProject('nonexistent', $this->project->getId()))->toBeNull();
    });

    it('deletes a merge request', function () {
        $mr = MergeRequest::create('del', 'Del', MergeRequestStatus::Open, new DateTimeImmutable(), null, 'A', $this->project);
        $this->repo->save($mr);

        $this->repo->delete($mr);
        $this->getEntityManager()->clear();

        expect($this->repo->findById($mr->getId()))->toBeNull();
    });
});
```

- [ ] **Step 6: Write DoctrineSyncJobRepositoryTest**

```php
<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\SyncJob;
use App\Catalog\Domain\Repository\SyncJobRepositoryInterface;
use App\Tests\Helpers\DatabaseHelper;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(SyncJobRepositoryInterface::class);
});

describe('DoctrineSyncJobRepository', function () {
    it('saves and finds a sync job by id', function () {
        $job = SyncJob::create(totalProjects: 5);
        $this->repo->save($job);

        $found = $this->repo->findById($job->getId());
        expect($found)->not->toBeNull();
        expect($found->getTotalProjects())->toBe(5);
    });

    it('returns null for unknown id', function () {
        expect($this->repo->findById(\Symfony\Component\Uid\Uuid::v7()))->toBeNull();
    });
});
```

- [ ] **Step 7: Run all Catalog integration tests**

```bash
docker compose exec php vendor/bin/pest tests/Integration/Catalog/ --verbose
```
Expected: All tests pass.

- [ ] **Step 8: Commit**

```bash
git add backend/tests/Integration/Catalog/
git commit -m "test(integration): add Catalog repository tests — Project, Provider, TechStack, MergeRequest, SyncJob"
```

---

### Task 4: Integration Tests — Dependency Repositories

**Files:**
- Create: `backend/tests/Integration/Dependency/DoctrineDependencyRepositoryTest.php`
- Create: `backend/tests/Integration/Dependency/DoctrineVulnerabilityRepositoryTest.php`
- Create: `backend/tests/Integration/Dependency/DoctrineDependencyVersionRepositoryTest.php`

- [ ] **Step 1: Write DoctrineDependencyRepositoryTest**

```php
<?php

declare(strict_types=1);

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\Vulnerability;
use App\Dependency\Domain\Model\Severity;
use App\Dependency\Domain\Model\VulnerabilityStatus;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use App\Tests\Helpers\DatabaseHelper;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(DependencyRepositoryInterface::class);
    $this->projectId = Uuid::v7();
});

describe('DoctrineDependencyRepository', function () {
    it('saves and finds by id', function () {
        $dep = Dependency::create('symfony/console', '7.0.0', '8.0.0', '7.4.0', PackageManager::Composer, DependencyType::Runtime, true, $this->projectId);
        $this->repo->save($dep);

        $found = $this->repo->findById($dep->getId());
        expect($found)->not->toBeNull();
        expect($found->getName())->toBe('symfony/console');
    });

    it('finds by project id with pagination', function () {
        for ($i = 0; $i < 3; $i++) {
            $this->repo->save(
                Dependency::create("pkg-{$i}", '1.0', '2.0', '1.5', PackageManager::Npm, DependencyType::Runtime, false, $this->projectId)
            );
        }

        expect($this->repo->findByProjectId($this->projectId, 1, 2))->toHaveCount(2);
    });

    it('counts by project id', function () {
        $this->repo->save(Dependency::create('a', '1.0', '2.0', '1.5', PackageManager::Composer, DependencyType::Runtime, false, $this->projectId));
        expect($this->repo->countByProjectId($this->projectId))->toBe(1);
    });

    it('deletes by project id', function () {
        $this->repo->save(Dependency::create('a', '1.0', '2.0', '1.5', PackageManager::Composer, DependencyType::Runtime, false, $this->projectId));
        $this->repo->save(Dependency::create('b', '1.0', '2.0', '1.5', PackageManager::Composer, DependencyType::Runtime, false, $this->projectId));

        $this->repo->deleteByProjectId($this->projectId);
        $this->getEntityManager()->clear();

        expect($this->repo->countByProjectId($this->projectId))->toBe(0);
    });

    it('filters dependencies', function () {
        $this->repo->save(Dependency::create('react', '17.0', '18.0', '17.0', PackageManager::Npm, DependencyType::Runtime, true, $this->projectId));
        $this->repo->save(Dependency::create('jest', '29.0', '29.5', '29.0', PackageManager::Npm, DependencyType::Dev, false, $this->projectId));
        $this->repo->save(Dependency::create('symfony/console', '7.0', '8.0', '7.4', PackageManager::Composer, DependencyType::Runtime, true, $this->projectId));

        $npmOnly = $this->repo->findFiltered(1, 20, ['packageManager' => 'npm']);
        expect($npmOnly)->toHaveCount(2);

        $outdated = $this->repo->findFiltered(1, 20, ['isOutdated' => true]);
        expect($outdated)->toHaveCount(2);

        $search = $this->repo->findFiltered(1, 20, ['search' => 'react']);
        expect($search)->toHaveCount(1);
    });

    it('counts filtered dependencies', function () {
        $this->repo->save(Dependency::create('a', '1.0', '2.0', '1.5', PackageManager::Npm, DependencyType::Runtime, true, $this->projectId));
        $this->repo->save(Dependency::create('b', '1.0', '1.0', '1.0', PackageManager::Npm, DependencyType::Runtime, false, $this->projectId));

        expect($this->repo->countFiltered(['isOutdated' => true]))->toBe(1);
    });

    it('finds unique packages', function () {
        $this->repo->save(Dependency::create('react', '17.0', '18.0', '17.0', PackageManager::Npm, DependencyType::Runtime, false, $this->projectId));

        $p2 = Uuid::v7();
        $this->repo->save(Dependency::create('react', '16.0', '18.0', '17.0', PackageManager::Npm, DependencyType::Runtime, true, $p2));
        $this->repo->save(Dependency::create('express', '4.0', '5.0', '4.18', PackageManager::Npm, DependencyType::Runtime, false, $p2));

        $unique = $this->repo->findUniquePackages();
        expect($unique)->toHaveCount(2);
    });

    it('finds by name and package manager', function () {
        $this->repo->save(Dependency::create('react', '17.0', '18.0', '17.0', PackageManager::Npm, DependencyType::Runtime, false, $this->projectId));
        $this->repo->save(Dependency::create('react', '16.0', '18.0', '17.0', PackageManager::Npm, DependencyType::Runtime, true, Uuid::v7()));

        $found = $this->repo->findByName('react', 'npm');
        expect($found)->toHaveCount(2);
    });

    it('returns stats', function () {
        $dep = Dependency::create('vuln-pkg', '1.0', '2.0', '1.5', PackageManager::Npm, DependencyType::Runtime, true, $this->projectId);
        $this->repo->save($dep);

        $this->repo->save(Dependency::create('safe-pkg', '1.0', '1.0', '1.0', PackageManager::Npm, DependencyType::Runtime, false, $this->projectId));

        $em = $this->getEntityManager();
        $vuln = Vulnerability::create('CVE-2026-001', Severity::High, 'Test', 'Desc', '2.0', VulnerabilityStatus::Open, new DateTimeImmutable(), $dep);
        $em->persist($vuln);
        $em->flush();

        $stats = $this->repo->getStats([]);
        expect($stats['total'])->toBe(2);
        expect($stats['outdated'])->toBe(1);
        expect($stats['totalVulnerabilities'])->toBe(1);
    });

    it('deletes a dependency', function () {
        $dep = Dependency::create('del', '1.0', '2.0', '1.5', PackageManager::Composer, DependencyType::Runtime, false, $this->projectId);
        $this->repo->save($dep);

        $this->repo->delete($dep);
        $this->getEntityManager()->clear();

        expect($this->repo->findById($dep->getId()))->toBeNull();
    });
});
```

- [ ] **Step 2: Run to verify**

```bash
docker compose exec php vendor/bin/pest tests/Integration/Dependency/DoctrineDependencyRepositoryTest.php --verbose
```
Expected: 10 tests pass.

- [ ] **Step 3: Write DoctrineVulnerabilityRepositoryTest**

```php
<?php

declare(strict_types=1);

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Model\Severity;
use App\Dependency\Domain\Model\Vulnerability;
use App\Dependency\Domain\Model\VulnerabilityStatus;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Dependency\Domain\Repository\VulnerabilityRepositoryInterface;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use App\Tests\Helpers\DatabaseHelper;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(VulnerabilityRepositoryInterface::class);
    $depRepo = self::getContainer()->get(DependencyRepositoryInterface::class);

    $this->dependency = Dependency::create('pkg', '1.0', '2.0', '1.5', PackageManager::Composer, DependencyType::Runtime, true, Uuid::v7());
    $depRepo->save($this->dependency);
});

describe('DoctrineVulnerabilityRepository', function () {
    it('saves and finds by id', function () {
        $vuln = Vulnerability::create('CVE-2026-001', Severity::Critical, 'RCE', 'Desc', '2.0', VulnerabilityStatus::Open, new DateTimeImmutable(), $this->dependency);
        $this->repo->save($vuln);

        $found = $this->repo->findById($vuln->getId());
        expect($found)->not->toBeNull();
        expect($found->getCveId())->toBe('CVE-2026-001');
    });

    it('lists with pagination', function () {
        for ($i = 0; $i < 3; $i++) {
            $this->repo->save(
                Vulnerability::create("CVE-2026-00{$i}", Severity::Medium, "Vuln {$i}", 'Desc', '2.0', VulnerabilityStatus::Open, new DateTimeImmutable(), $this->dependency)
            );
        }

        expect($this->repo->findAll(1, 2))->toHaveCount(2);
    });

    it('counts vulnerabilities', function () {
        expect($this->repo->count())->toBe(0);
        $this->repo->save(Vulnerability::create('CVE-2026-999', Severity::Low, 'V', 'D', '2.0', VulnerabilityStatus::Open, new DateTimeImmutable(), $this->dependency));
        expect($this->repo->count())->toBe(1);
    });
});
```

- [ ] **Step 4: Write DoctrineDependencyVersionRepositoryTest**

```php
<?php

declare(strict_types=1);

use App\Dependency\Domain\Model\DependencyVersion;
use App\Dependency\Domain\Repository\DependencyVersionRepositoryInterface;
use App\Shared\Domain\ValueObject\PackageManager;
use App\Tests\Helpers\DatabaseHelper;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(DependencyVersionRepositoryInterface::class);
});

describe('DoctrineDependencyVersionRepository', function () {
    it('saves and finds by name and manager', function () {
        $v = DependencyVersion::create('react', PackageManager::Npm, '18.0.0', new DateTimeImmutable(), true);
        $this->repo->save($v);

        $found = $this->repo->findByNameAndManager('react', PackageManager::Npm);
        expect($found)->toHaveCount(1);
        expect($found[0]->getVersion())->toBe('18.0.0');
    });

    it('finds latest by name and manager', function () {
        $old = DependencyVersion::create('react', PackageManager::Npm, '17.0.0', new DateTimeImmutable('-1 day'), false);
        $this->repo->save($old);

        $latest = DependencyVersion::create('react', PackageManager::Npm, '18.2.0', new DateTimeImmutable(), true);
        $this->repo->save($latest);

        $found = $this->repo->findLatestByNameAndManager('react', PackageManager::Npm);
        expect($found)->not->toBeNull();
        expect($found->getVersion())->toBe('18.2.0');
    });

    it('finds by name manager and version', function () {
        $v = DependencyVersion::create('express', PackageManager::Npm, '4.18.2', new DateTimeImmutable(), true);
        $this->repo->save($v);

        $found = $this->repo->findByNameManagerAndVersion('express', PackageManager::Npm, '4.18.2');
        expect($found)->not->toBeNull();

        expect($this->repo->findByNameManagerAndVersion('express', PackageManager::Npm, '9.9.9'))->toBeNull();
    });

    it('clears latest flag', function () {
        $v = DependencyVersion::create('lodash', PackageManager::Npm, '4.17.21', new DateTimeImmutable(), true);
        $this->repo->save($v);

        $this->repo->clearLatestFlag('lodash', PackageManager::Npm);
        $this->getEntityManager()->clear();

        $found = $this->repo->findLatestByNameAndManager('lodash', PackageManager::Npm);
        expect($found)->toBeNull();
    });
});
```

- [ ] **Step 5: Run all Dependency integration tests**

```bash
docker compose exec php vendor/bin/pest tests/Integration/Dependency/ --verbose
```
Expected: All tests pass.

- [ ] **Step 6: Commit**

```bash
git add backend/tests/Integration/Dependency/
git commit -m "test(integration): add Dependency repository tests — Dependency, Vulnerability, DependencyVersion"
```

---

### Task 5: Integration Tests — Activity Repositories

**Files:**
- Create: `backend/tests/Integration/Activity/DoctrineActivityEventRepositoryTest.php`
- Create: `backend/tests/Integration/Activity/DoctrineNotificationRepositoryTest.php`
- Create: `backend/tests/Integration/Activity/DoctrineBuildMetricRepositoryTest.php`
- Create: `backend/tests/Integration/Activity/DoctrineSyncTaskRepositoryTest.php`

- [ ] **Step 1: Write DoctrineActivityEventRepositoryTest**

```php
<?php

declare(strict_types=1);

use App\Activity\Domain\Model\ActivityEvent;
use App\Activity\Domain\Repository\ActivityEventRepositoryInterface;
use App\Tests\Helpers\DatabaseHelper;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(ActivityEventRepositoryInterface::class);
});

describe('DoctrineActivityEventRepository', function () {
    it('saves and finds by id', function () {
        $event = ActivityEvent::create('project.created', 'Project', 'p-1', ['name' => 'Test'], 'user-1');
        $this->repo->save($event);

        $found = $this->repo->findById($event->getId());
        expect($found)->not->toBeNull();
        expect($found->getType())->toBe('project.created');
    });

    it('lists with pagination', function () {
        for ($i = 0; $i < 3; $i++) {
            $this->repo->save(ActivityEvent::create("type.{$i}", 'Entity', "e-{$i}", [], 'user-1'));
        }

        expect($this->repo->findAll(1, 2))->toHaveCount(2);
    });

    it('counts events', function () {
        expect($this->repo->count())->toBe(0);
        $this->repo->save(ActivityEvent::create('t', 'E', 'id', [], 'u'));
        expect($this->repo->count())->toBe(1);
    });
});
```

- [ ] **Step 2: Write DoctrineNotificationRepositoryTest**

```php
<?php

declare(strict_types=1);

use App\Activity\Domain\Model\Notification;
use App\Activity\Domain\Model\NotificationChannel;
use App\Activity\Domain\Repository\NotificationRepositoryInterface;
use App\Tests\Helpers\DatabaseHelper;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(NotificationRepositoryInterface::class);
    $this->userId = 'user-123';
});

describe('DoctrineNotificationRepository', function () {
    it('saves and finds by id', function () {
        $notif = Notification::create('Title', 'Message', NotificationChannel::InApp, $this->userId);
        $this->repo->save($notif);

        $found = $this->repo->findById($notif->getId());
        expect($found)->not->toBeNull();
        expect($found->getTitle())->toBe('Title');
    });

    it('finds by user with pagination', function () {
        for ($i = 0; $i < 3; $i++) {
            $this->repo->save(Notification::create("N{$i}", 'Msg', NotificationChannel::InApp, $this->userId));
        }

        expect($this->repo->findByUser($this->userId, 1, 2))->toHaveCount(2);
    });

    it('counts by user', function () {
        $this->repo->save(Notification::create('N', 'M', NotificationChannel::InApp, $this->userId));
        expect($this->repo->countByUser($this->userId))->toBe(1);
    });

    it('counts unread by user', function () {
        $n1 = Notification::create('Unread', 'M', NotificationChannel::InApp, $this->userId);
        $this->repo->save($n1);

        $n2 = Notification::create('Read', 'M', NotificationChannel::InApp, $this->userId);
        $n2->markRead();
        $this->repo->save($n2);

        expect($this->repo->countUnreadByUser($this->userId))->toBe(1);
    });
});
```

- [ ] **Step 3: Write DoctrineBuildMetricRepositoryTest**

```php
<?php

declare(strict_types=1);

use App\Activity\Domain\Model\BuildMetric;
use App\Activity\Domain\Repository\BuildMetricRepositoryInterface;
use App\Tests\Helpers\DatabaseHelper;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(BuildMetricRepositoryInterface::class);
    $this->projectId = Uuid::v7();
});

describe('DoctrineBuildMetricRepository', function () {
    it('saves and finds by id', function () {
        $metric = BuildMetric::create($this->projectId, 120, true, 85.5, 80.0, 'main', 'abc123');
        $this->repo->save($metric);

        $found = $this->repo->findById($metric->getId());
        expect($found)->not->toBeNull();
        expect($found->getDuration())->toBe(120);
    });

    it('finds by project id with pagination', function () {
        for ($i = 0; $i < 3; $i++) {
            $this->repo->save(BuildMetric::create($this->projectId, $i * 10, true, 80.0, 80.0, 'main', "sha{$i}"));
        }

        expect($this->repo->findByProjectId($this->projectId, 1, 2))->toHaveCount(2);
    });

    it('counts by project id', function () {
        $this->repo->save(BuildMetric::create($this->projectId, 60, true, 90.0, 85.0, 'main', 'sha1'));
        expect($this->repo->countByProjectId($this->projectId))->toBe(1);
    });

    it('finds latest by project id', function () {
        $this->repo->save(BuildMetric::create($this->projectId, 60, true, 80.0, 80.0, 'main', 'old'));
        usleep(10000); // ensure different createdAt
        $this->repo->save(BuildMetric::create($this->projectId, 90, false, 70.0, 60.0, 'main', 'new'));

        $latest = $this->repo->findLatestByProjectId($this->projectId);
        expect($latest)->not->toBeNull();
        expect($latest->getCommitSha())->toBe('new');
    });
});
```

- [ ] **Step 4: Write DoctrineSyncTaskRepositoryTest**

```php
<?php

declare(strict_types=1);

use App\Activity\Domain\Model\SyncTask;
use App\Activity\Domain\Model\SyncTaskSeverity;
use App\Activity\Domain\Model\SyncTaskStatus;
use App\Activity\Domain\Model\SyncTaskType;
use App\Activity\Domain\Repository\SyncTaskRepositoryInterface;
use App\Tests\Helpers\DatabaseHelper;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->repo = self::getContainer()->get(SyncTaskRepositoryInterface::class);
    $this->projectId = Uuid::v7();
});

describe('DoctrineSyncTaskRepository', function () {
    it('saves and finds by id', function () {
        $task = SyncTask::create(
            SyncTaskType::Vulnerability, SyncTaskSeverity::High, 'CVE found', 'Desc',
            $this->projectId, ['cveId' => 'CVE-2026-001']
        );
        $this->repo->save($task);

        $found = $this->repo->findById($task->getId());
        expect($found)->not->toBeNull();
        expect($found->getTitle())->toBe('CVE found');
    });

    it('filters by status', function () {
        $this->repo->save(SyncTask::create(SyncTaskType::Vulnerability, SyncTaskSeverity::High, 'T1', 'D', $this->projectId, []));
        $resolved = SyncTask::create(SyncTaskType::Vulnerability, SyncTaskSeverity::Low, 'T2', 'D', $this->projectId, []);
        $resolved->changeStatus(SyncTaskStatus::Resolved);
        $this->repo->save($resolved);

        $open = $this->repo->findFiltered(status: SyncTaskStatus::Open);
        expect($open)->toHaveCount(1);

        $all = $this->repo->findFiltered();
        expect($all)->toHaveCount(2);
    });

    it('filters by type and severity', function () {
        $this->repo->save(SyncTask::create(SyncTaskType::Vulnerability, SyncTaskSeverity::Critical, 'V', 'D', $this->projectId, []));
        $this->repo->save(SyncTask::create(SyncTaskType::OutdatedDependency, SyncTaskSeverity::Low, 'O', 'D', $this->projectId, []));

        $vulns = $this->repo->findFiltered(type: SyncTaskType::Vulnerability);
        expect($vulns)->toHaveCount(1);

        $critical = $this->repo->findFiltered(severity: SyncTaskSeverity::Critical);
        expect($critical)->toHaveCount(1);
    });

    it('filters by project id', function () {
        $otherId = Uuid::v7();
        $this->repo->save(SyncTask::create(SyncTaskType::StalePr, SyncTaskSeverity::Medium, 'T1', 'D', $this->projectId, []));
        $this->repo->save(SyncTask::create(SyncTaskType::StalePr, SyncTaskSeverity::Medium, 'T2', 'D', $otherId, []));

        $filtered = $this->repo->findFiltered(projectId: $this->projectId);
        expect($filtered)->toHaveCount(1);
    });

    it('counts filtered', function () {
        $this->repo->save(SyncTask::create(SyncTaskType::Vulnerability, SyncTaskSeverity::High, 'T', 'D', $this->projectId, []));
        $this->repo->save(SyncTask::create(SyncTaskType::OutdatedDependency, SyncTaskSeverity::Low, 'T', 'D', $this->projectId, []));

        expect($this->repo->countFiltered())->toBe(2);
        expect($this->repo->countFiltered(type: SyncTaskType::Vulnerability))->toBe(1);
    });

    it('finds open task by project type and metadata key', function () {
        $task = SyncTask::create(
            SyncTaskType::Vulnerability, SyncTaskSeverity::High, 'CVE',
            'Desc', $this->projectId, ['cveId' => 'CVE-2026-001']
        );
        $this->repo->save($task);

        $found = $this->repo->findOpenByProjectAndTypeAndKey($this->projectId, SyncTaskType::Vulnerability, 'CVE-2026-001');
        expect($found)->not->toBeNull();

        expect($this->repo->findOpenByProjectAndTypeAndKey($this->projectId, SyncTaskType::Vulnerability, 'CVE-NONE'))->toBeNull();
    });

    it('counts grouped by type', function () {
        $this->repo->save(SyncTask::create(SyncTaskType::Vulnerability, SyncTaskSeverity::High, 'T', 'D', $this->projectId, []));
        $this->repo->save(SyncTask::create(SyncTaskType::Vulnerability, SyncTaskSeverity::Low, 'T', 'D', $this->projectId, []));
        $this->repo->save(SyncTask::create(SyncTaskType::StalePr, SyncTaskSeverity::Medium, 'T', 'D', $this->projectId, []));

        $grouped = $this->repo->countGroupedByType();
        expect($grouped)->toBeArray();
        expect(\count($grouped))->toBeGreaterThanOrEqual(2);
    });

    it('counts grouped by severity', function () {
        $this->repo->save(SyncTask::create(SyncTaskType::Vulnerability, SyncTaskSeverity::Critical, 'T', 'D', $this->projectId, []));
        $this->repo->save(SyncTask::create(SyncTaskType::Vulnerability, SyncTaskSeverity::Critical, 'T', 'D', $this->projectId, []));

        $grouped = $this->repo->countGroupedBySeverity();
        expect($grouped)->toBeArray();
    });

    it('counts grouped by status', function () {
        $this->repo->save(SyncTask::create(SyncTaskType::Vulnerability, SyncTaskSeverity::High, 'T', 'D', $this->projectId, []));

        $grouped = $this->repo->countGroupedByStatus();
        expect($grouped)->toBeArray();
    });
});
```

- [ ] **Step 5: Run all Activity integration tests**

```bash
docker compose exec php vendor/bin/pest tests/Integration/Activity/ --verbose
```
Expected: All tests pass.

- [ ] **Step 6: Commit**

```bash
git add backend/tests/Integration/Activity/
git commit -m "test(integration): add Activity repository tests — ActivityEvent, Notification, BuildMetric, SyncTask"
```

---

### Task 6: Functional Tests — Auth & Identity Endpoints

**Files:**
- Create: `backend/tests/Functional/Identity/AuthEndpointsTest.php`
- Create: `backend/tests/Functional/Identity/UserEndpointsTest.php`
- Create: `backend/tests/Functional/Identity/AccessTokenEndpointsTest.php`

- [ ] **Step 1: Write AuthEndpointsTest**

```php
<?php

declare(strict_types=1);

use App\Tests\Helpers\AuthHelper;
use App\Tests\Helpers\DatabaseHelper;

uses(DatabaseHelper::class, AuthHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->client = static::createClient();
});

describe('POST /api/auth/register', function () {
    it('registers a new user and returns 201', function () {
        $this->client->request('POST', '/api/auth/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'new@example.com',
            'password' => 'securepass123',
            'firstName' => 'New',
            'lastName' => 'User',
        ]));

        expect($this->client->getResponse()->getStatusCode())->toBe(201);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        expect($data['success'])->toBeTrue();
        expect($data['data']['email'])->toBe('new@example.com');
    });

    it('returns 422 for duplicate email', function () {
        $this->createAuthenticatedUser(['email' => 'dupe@example.com']);

        $this->client->request('POST', '/api/auth/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'dupe@example.com',
            'password' => 'securepass123',
            'firstName' => 'Dupe',
            'lastName' => 'User',
        ]));

        expect($this->client->getResponse()->getStatusCode())->toBe(422);
    });

    it('returns 422 for invalid email', function () {
        $this->client->request('POST', '/api/auth/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'not-an-email',
            'password' => 'securepass123',
            'firstName' => 'Bad',
            'lastName' => 'Email',
        ]));

        expect($this->client->getResponse()->getStatusCode())->toBe(422);
    });

    it('returns 422 for short password', function () {
        $this->client->request('POST', '/api/auth/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'short@example.com',
            'password' => 'short',
            'firstName' => 'Short',
            'lastName' => 'Pass',
        ]));

        expect($this->client->getResponse()->getStatusCode())->toBe(422);
    });
});

describe('POST /api/auth/login', function () {
    it('logs in with valid credentials and returns token', function () {
        $this->createAuthenticatedUser(['email' => 'login@example.com', 'password' => 'password123']);

        $this->client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'login@example.com',
            'password' => 'password123',
        ]));

        expect($this->client->getResponse()->getStatusCode())->toBe(200);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        expect($data['success'])->toBeTrue();
        expect($data['data'])->toHaveKey('token');
        expect($data['data'])->toHaveKey('user');
    });

    it('returns 401 for wrong password', function () {
        $this->createAuthenticatedUser(['email' => 'wrong@example.com', 'password' => 'password123']);

        $this->client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]));

        expect($this->client->getResponse()->getStatusCode())->toBe(401);
    });
});

describe('POST /api/auth/logout', function () {
    it('logs out and returns 200', function () {
        ['token' => $token] = $this->createAuthenticatedUser();

        $this->client->request('POST', '/api/auth/logout', [], [], $this->authHeader($token));

        expect($this->client->getResponse()->getStatusCode())->toBe(200);
    });
});

describe('GET /api/auth/profile', function () {
    it('returns current user profile', function () {
        ['token' => $token] = $this->createAuthenticatedUser(['email' => 'me@example.com']);

        $this->client->request('GET', '/api/auth/profile', [], [], $this->authHeader($token));

        expect($this->client->getResponse()->getStatusCode())->toBe(200);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        expect($data['success'])->toBeTrue();
        expect($data['data']['email'])->toBe('me@example.com');
    });

    it('returns 401 without token', function () {
        $this->client->request('GET', '/api/auth/profile');

        expect($this->client->getResponse()->getStatusCode())->toBe(401);
    });
});
```

- [ ] **Step 2: Run to verify**

```bash
docker compose exec php vendor/bin/pest tests/Functional/Identity/AuthEndpointsTest.php --verbose
```
Expected: All tests pass.

- [ ] **Step 3: Write UserEndpointsTest**

```php
<?php

declare(strict_types=1);

use App\Tests\Helpers\AuthHelper;
use App\Tests\Helpers\DatabaseHelper;

uses(DatabaseHelper::class, AuthHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->client = static::createClient();
    ['user' => $this->user, 'token' => $this->token] = $this->createAuthenticatedUser();
});

describe('GET /api/identity/users', function () {
    it('lists users', function () {
        $this->client->request('GET', '/api/identity/users', [], [], $this->authHeader($this->token));

        expect($this->client->getResponse()->getStatusCode())->toBe(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        expect($data['success'])->toBeTrue();
        expect($data['data'])->toHaveKey('items');
        expect($data['data'])->toHaveKey('total');
    });

    it('returns 401 without auth', function () {
        $this->client->request('GET', '/api/identity/users');
        expect($this->client->getResponse()->getStatusCode())->toBe(401);
    });
});

describe('GET /api/identity/users/{id}', function () {
    it('gets a user by id', function () {
        $this->client->request('GET', '/api/identity/users/' . $this->user->getId()->toRfc4122(), [], [], $this->authHeader($this->token));

        expect($this->client->getResponse()->getStatusCode())->toBe(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        expect($data['data']['email'])->toBe('test@example.com');
    });

    it('returns 404 for unknown user', function () {
        $this->client->request('GET', '/api/identity/users/' . \Symfony\Component\Uid\Uuid::v7()->toRfc4122(), [], [], $this->authHeader($this->token));

        expect($this->client->getResponse()->getStatusCode())->toBe(404);
    });
});

describe('PUT /api/identity/users/{id}', function () {
    it('updates a user', function () {
        $this->client->request('PUT', '/api/identity/users/' . $this->user->getId()->toRfc4122(), [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'firstName' => 'Updated',
        ]));

        expect($this->client->getResponse()->getStatusCode())->toBe(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        expect($data['data']['firstName'])->toBe('Updated');
    });
});
```

- [ ] **Step 4: Write AccessTokenEndpointsTest**

```php
<?php

declare(strict_types=1);

use App\Tests\Helpers\AuthHelper;
use App\Tests\Helpers\DatabaseHelper;

uses(DatabaseHelper::class, AuthHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->client = static::createClient();
    ['user' => $this->user, 'token' => $this->token] = $this->createAuthenticatedUser();
});

describe('POST /api/identity/access-tokens', function () {
    it('creates an access token and returns 201', function () {
        $this->client->request('POST', '/api/identity/access-tokens', [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'provider' => 'gitlab',
            'token' => 'glpat-test-token',
            'scopes' => ['api', 'read_user'],
        ]));

        expect($this->client->getResponse()->getStatusCode())->toBe(201);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        expect($data['success'])->toBeTrue();
    });
});

describe('GET /api/identity/access-tokens', function () {
    it('lists access tokens for current user', function () {
        $this->client->request('GET', '/api/identity/access-tokens', [], [], $this->authHeader($this->token));

        expect($this->client->getResponse()->getStatusCode())->toBe(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        expect($data['data'])->toHaveKey('items');
    });
});

describe('DELETE /api/identity/access-tokens/{id}', function () {
    it('deletes an access token and returns 204', function () {
        // Create a token first
        $this->client->request('POST', '/api/identity/access-tokens', [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'provider' => 'gitlab',
            'token' => 'glpat-to-delete',
            'scopes' => ['api'],
        ]));
        $createData = json_decode($this->client->getResponse()->getContent(), true);
        $tokenId = $createData['data']['id'];

        $this->client->request('DELETE', "/api/identity/access-tokens/{$tokenId}", [], [], $this->authHeader($this->token));

        expect($this->client->getResponse()->getStatusCode())->toBe(204);
    });
});
```

- [ ] **Step 5: Run all functional Identity tests**

```bash
docker compose exec php vendor/bin/pest tests/Functional/Identity/ --verbose
```
Expected: All tests pass.

- [ ] **Step 6: Commit**

```bash
git add backend/tests/Functional/Identity/
git rm backend/tests/Functional/.gitkeep 2>/dev/null; true
git commit -m "test(functional): add Identity endpoint tests — auth, users, access tokens"
```

---

### Task 7: Functional Tests — Catalog Endpoints

**Files:**
- Create: `backend/tests/Functional/Catalog/ProjectEndpointsTest.php`
- Create: `backend/tests/Functional/Catalog/ProviderEndpointsTest.php`
- Create: `backend/tests/Functional/Catalog/TechStackEndpointsTest.php`
- Create: `backend/tests/Functional/Catalog/MergeRequestEndpointsTest.php`

- [ ] **Step 1: Write ProjectEndpointsTest**

```php
<?php

declare(strict_types=1);

use App\Tests\Helpers\AuthHelper;
use App\Tests\Helpers\DatabaseHelper;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class, AuthHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->client = static::createClient();
    ['user' => $this->user, 'token' => $this->token] = $this->createAuthenticatedUser();
});

describe('POST /api/catalog/projects', function () {
    it('creates a project and returns 201', function () {
        $this->client->request('POST', '/api/catalog/projects', [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'name' => 'My Project',
            'slug' => 'my-project',
            'description' => 'A test project',
            'repositoryUrl' => 'https://github.com/test/project',
            'defaultBranch' => 'main',
            'visibility' => 'private',
            'ownerId' => $this->user->getId()->toRfc4122(),
        ]));

        expect($this->client->getResponse()->getStatusCode())->toBe(201);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        expect($data['success'])->toBeTrue();
        expect($data['data']['name'])->toBe('My Project');
        expect($data['data']['slug'])->toBe('my-project');
    });

    it('returns 422 for duplicate slug', function () {
        // Create first project
        $this->client->request('POST', '/api/catalog/projects', [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'name' => 'First', 'slug' => 'dup-slug', 'repositoryUrl' => 'https://github.com/t/1',
            'defaultBranch' => 'main', 'visibility' => 'private', 'ownerId' => $this->user->getId()->toRfc4122(),
        ]));
        expect($this->client->getResponse()->getStatusCode())->toBe(201);

        // Duplicate slug
        $this->client->request('POST', '/api/catalog/projects', [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'name' => 'Second', 'slug' => 'dup-slug', 'repositoryUrl' => 'https://github.com/t/2',
            'defaultBranch' => 'main', 'visibility' => 'private', 'ownerId' => $this->user->getId()->toRfc4122(),
        ]));

        expect($this->client->getResponse()->getStatusCode())->toBe(422);
    });

    it('returns 422 for invalid slug format', function () {
        $this->client->request('POST', '/api/catalog/projects', [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'name' => 'Bad Slug', 'slug' => 'BAD SLUG!', 'repositoryUrl' => 'https://github.com/t/1',
            'defaultBranch' => 'main', 'visibility' => 'private', 'ownerId' => $this->user->getId()->toRfc4122(),
        ]));

        expect($this->client->getResponse()->getStatusCode())->toBe(422);
    });
});

describe('GET /api/catalog/projects', function () {
    it('lists projects with pagination', function () {
        $this->client->request('GET', '/api/catalog/projects?page=1&per_page=10', [], [], $this->authHeader($this->token));

        expect($this->client->getResponse()->getStatusCode())->toBe(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        expect($data['data'])->toHaveKey('items');
        expect($data['data'])->toHaveKey('total');
    });
});

describe('GET /api/catalog/projects/{id}', function () {
    it('gets a project by id', function () {
        // Create project first
        $this->client->request('POST', '/api/catalog/projects', [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'name' => 'Get Test', 'slug' => 'get-test', 'repositoryUrl' => 'https://github.com/t/g',
            'defaultBranch' => 'main', 'visibility' => 'private', 'ownerId' => $this->user->getId()->toRfc4122(),
        ]));
        $projectId = json_decode($this->client->getResponse()->getContent(), true)['data']['id'];

        $this->client->request('GET', "/api/catalog/projects/{$projectId}", [], [], $this->authHeader($this->token));

        expect($this->client->getResponse()->getStatusCode())->toBe(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        expect($data['data']['slug'])->toBe('get-test');
    });

    it('returns 404 for unknown project', function () {
        $fakeId = Uuid::v7()->toRfc4122();
        $this->client->request('GET', "/api/catalog/projects/{$fakeId}", [], [], $this->authHeader($this->token));

        expect($this->client->getResponse()->getStatusCode())->toBe(404);
    });
});

describe('PUT /api/catalog/projects/{id}', function () {
    it('updates a project', function () {
        $this->client->request('POST', '/api/catalog/projects', [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'name' => 'Original', 'slug' => 'original', 'repositoryUrl' => 'https://github.com/t/o',
            'defaultBranch' => 'main', 'visibility' => 'private', 'ownerId' => $this->user->getId()->toRfc4122(),
        ]));
        $projectId = json_decode($this->client->getResponse()->getContent(), true)['data']['id'];

        $this->client->request('PUT', "/api/catalog/projects/{$projectId}", [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'name' => 'Updated Name',
        ]));

        expect($this->client->getResponse()->getStatusCode())->toBe(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        expect($data['data']['name'])->toBe('Updated Name');
    });
});

describe('DELETE /api/catalog/projects/{id}', function () {
    it('deletes a project and returns 204', function () {
        $this->client->request('POST', '/api/catalog/projects', [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'name' => 'To Delete', 'slug' => 'to-delete', 'repositoryUrl' => 'https://github.com/t/d',
            'defaultBranch' => 'main', 'visibility' => 'private', 'ownerId' => $this->user->getId()->toRfc4122(),
        ]));
        $projectId = json_decode($this->client->getResponse()->getContent(), true)['data']['id'];

        $this->client->request('DELETE', "/api/catalog/projects/{$projectId}", [], [], $this->authHeader($this->token));

        expect($this->client->getResponse()->getStatusCode())->toBe(204);
    });
});
```

- [ ] **Step 2: Run to verify**

```bash
docker compose exec php vendor/bin/pest tests/Functional/Catalog/ProjectEndpointsTest.php --verbose
```
Expected: All tests pass.

- [ ] **Step 3: Write ProviderEndpointsTest**

```php
<?php

declare(strict_types=1);

use App\Tests\Helpers\AuthHelper;
use App\Tests\Helpers\DatabaseHelper;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class, AuthHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->client = static::createClient();
    ['token' => $this->token] = $this->createAuthenticatedUser();
});

describe('POST /api/catalog/providers', function () {
    it('creates a provider and returns 201', function () {
        $this->client->request('POST', '/api/catalog/providers', [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'name' => 'GitLab Corp',
            'type' => 'gitlab',
            'url' => 'https://gitlab.corp.com',
            'apiToken' => 'glpat-test-token',
        ]));

        expect($this->client->getResponse()->getStatusCode())->toBe(201);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        expect($data['data']['name'])->toBe('GitLab Corp');
    });
});

describe('GET /api/catalog/providers', function () {
    it('lists providers', function () {
        $this->client->request('GET', '/api/catalog/providers', [], [], $this->authHeader($this->token));

        expect($this->client->getResponse()->getStatusCode())->toBe(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        expect($data['data'])->toHaveKey('items');
    });
});

describe('GET /api/catalog/providers/{id}', function () {
    it('gets a provider', function () {
        $this->client->request('POST', '/api/catalog/providers', [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'name' => 'GL', 'type' => 'gitlab', 'url' => 'https://gl.com', 'apiToken' => 'tok',
        ]));
        $id = json_decode($this->client->getResponse()->getContent(), true)['data']['id'];

        $this->client->request('GET', "/api/catalog/providers/{$id}", [], [], $this->authHeader($this->token));

        expect($this->client->getResponse()->getStatusCode())->toBe(200);
    });

    it('returns 404 for unknown provider', function () {
        $this->client->request('GET', '/api/catalog/providers/' . Uuid::v7()->toRfc4122(), [], [], $this->authHeader($this->token));
        expect($this->client->getResponse()->getStatusCode())->toBe(404);
    });
});

describe('PUT /api/catalog/providers/{id}', function () {
    it('updates a provider', function () {
        $this->client->request('POST', '/api/catalog/providers', [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'name' => 'Old', 'type' => 'gitlab', 'url' => 'https://old.com', 'apiToken' => 'tok',
        ]));
        $id = json_decode($this->client->getResponse()->getContent(), true)['data']['id'];

        $this->client->request('PUT', "/api/catalog/providers/{$id}", [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'name' => 'Updated',
        ]));

        expect($this->client->getResponse()->getStatusCode())->toBe(200);
    });
});

describe('DELETE /api/catalog/providers/{id}', function () {
    it('deletes a provider and returns 204', function () {
        $this->client->request('POST', '/api/catalog/providers', [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'name' => 'Del', 'type' => 'gitlab', 'url' => 'https://del.com', 'apiToken' => 'tok',
        ]));
        $id = json_decode($this->client->getResponse()->getContent(), true)['data']['id'];

        $this->client->request('DELETE', "/api/catalog/providers/{$id}", [], [], $this->authHeader($this->token));

        expect($this->client->getResponse()->getStatusCode())->toBe(204);
    });
});
```

- [ ] **Step 4: Write TechStackEndpointsTest**

```php
<?php

declare(strict_types=1);

use App\Tests\Helpers\AuthHelper;
use App\Tests\Helpers\DatabaseHelper;

uses(DatabaseHelper::class, AuthHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->client = static::createClient();
    ['user' => $this->user, 'token' => $this->token] = $this->createAuthenticatedUser();
});

describe('Tech Stack endpoints', function () {
    it('creates, lists, gets, and deletes a tech stack', function () {
        // Create project first
        $this->client->request('POST', '/api/catalog/projects', [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'name' => 'TS Project', 'slug' => 'ts-project', 'repositoryUrl' => 'https://github.com/t/ts',
            'defaultBranch' => 'main', 'visibility' => 'private', 'ownerId' => $this->user->getId()->toRfc4122(),
        ]));
        $projectId = json_decode($this->client->getResponse()->getContent(), true)['data']['id'];

        // Create tech stack
        $this->client->request('POST', '/api/catalog/tech-stacks', [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'language' => 'PHP',
            'framework' => 'Symfony',
            'version' => '8.4',
            'frameworkVersion' => '8.0',
            'projectId' => $projectId,
        ]));
        expect($this->client->getResponse()->getStatusCode())->toBe(201);
        $tsId = json_decode($this->client->getResponse()->getContent(), true)['data']['id'];

        // List
        $this->client->request('GET', "/api/catalog/tech-stacks?project_id={$projectId}", [], [], $this->authHeader($this->token));
        expect($this->client->getResponse()->getStatusCode())->toBe(200);

        // Get
        $this->client->request('GET', "/api/catalog/tech-stacks/{$tsId}", [], [], $this->authHeader($this->token));
        expect($this->client->getResponse()->getStatusCode())->toBe(200);

        // Delete
        $this->client->request('DELETE', "/api/catalog/tech-stacks/{$tsId}", [], [], $this->authHeader($this->token));
        expect($this->client->getResponse()->getStatusCode())->toBe(204);
    });
});
```

- [ ] **Step 5: Write MergeRequestEndpointsTest**

```php
<?php

declare(strict_types=1);

use App\Catalog\Domain\Model\MergeRequest;
use App\Catalog\Domain\Model\MergeRequestStatus;
use App\Catalog\Domain\Repository\MergeRequestRepositoryInterface;
use App\Catalog\Domain\Repository\ProjectRepositoryInterface;
use App\Tests\Helpers\AuthHelper;
use App\Tests\Helpers\DatabaseHelper;

uses(DatabaseHelper::class, AuthHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->client = static::createClient();
    ['user' => $this->user, 'token' => $this->token] = $this->createAuthenticatedUser();
});

describe('Merge Request endpoints', function () {
    it('lists and gets merge requests for a project', function () {
        // Create project via API
        $this->client->request('POST', '/api/catalog/projects', [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'name' => 'MR Project', 'slug' => 'mr-project', 'repositoryUrl' => 'https://github.com/t/mr',
            'defaultBranch' => 'main', 'visibility' => 'private', 'ownerId' => $this->user->getId()->toRfc4122(),
        ]));
        $projectId = json_decode($this->client->getResponse()->getContent(), true)['data']['id'];

        // Insert MR directly via repository (no create endpoint)
        $projectRepo = self::getContainer()->get(ProjectRepositoryInterface::class);
        $project = $projectRepo->findById(\Symfony\Component\Uid\Uuid::fromRfc4122($projectId));
        $mrRepo = self::getContainer()->get(MergeRequestRepositoryInterface::class);
        $mr = MergeRequest::create('ext-1', 'Fix issue', MergeRequestStatus::Open, new DateTimeImmutable(), null, 'Alice', $project);
        $mrRepo->save($mr);

        // List MRs
        $this->client->request('GET', "/api/catalog/projects/{$projectId}/merge-requests", [], [], $this->authHeader($this->token));
        expect($this->client->getResponse()->getStatusCode())->toBe(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        expect($data['data']['items'])->toHaveCount(1);

        // Get single MR
        $mrId = $mr->getId()->toRfc4122();
        $this->client->request('GET', "/api/catalog/merge-requests/{$mrId}", [], [], $this->authHeader($this->token));
        expect($this->client->getResponse()->getStatusCode())->toBe(200);
    });
});
```

- [ ] **Step 6: Run all Catalog functional tests**

```bash
docker compose exec php vendor/bin/pest tests/Functional/Catalog/ --verbose
```
Expected: All tests pass.

- [ ] **Step 7: Commit**

```bash
git add backend/tests/Functional/Catalog/
git commit -m "test(functional): add Catalog endpoint tests — projects, providers, tech stacks, merge requests"
```

---

### Task 8: Functional Tests — Dependency Endpoints

**Files:**
- Create: `backend/tests/Functional/Dependency/DependencyEndpointsTest.php`
- Create: `backend/tests/Functional/Dependency/VulnerabilityEndpointsTest.php`

- [ ] **Step 1: Write DependencyEndpointsTest**

```php
<?php

declare(strict_types=1);

use App\Tests\Helpers\AuthHelper;
use App\Tests\Helpers\DatabaseHelper;

uses(DatabaseHelper::class, AuthHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->client = static::createClient();
    ['user' => $this->user, 'token' => $this->token] = $this->createAuthenticatedUser();
});

describe('POST /api/dependency/dependencies', function () {
    it('creates a dependency and returns 201', function () {
        // Create project first
        $this->client->request('POST', '/api/catalog/projects', [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'name' => 'Dep Project', 'slug' => 'dep-project', 'repositoryUrl' => 'https://github.com/t/d',
            'defaultBranch' => 'main', 'visibility' => 'private', 'ownerId' => $this->user->getId()->toRfc4122(),
        ]));
        $projectId = json_decode($this->client->getResponse()->getContent(), true)['data']['id'];

        $this->client->request('POST', '/api/dependency/dependencies', [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'name' => 'symfony/console',
            'currentVersion' => '7.0.0',
            'latestVersion' => '8.0.0',
            'ltsVersion' => '7.4.0',
            'packageManager' => 'composer',
            'type' => 'runtime',
            'isOutdated' => true,
            'projectId' => $projectId,
        ]));

        expect($this->client->getResponse()->getStatusCode())->toBe(201);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        expect($data['data']['name'])->toBe('symfony/console');
    });
});

describe('GET /api/dependency/dependencies', function () {
    it('lists dependencies with filters', function () {
        $this->client->request('GET', '/api/dependency/dependencies?page=1&per_page=20', [], [], $this->authHeader($this->token));

        expect($this->client->getResponse()->getStatusCode())->toBe(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        expect($data['data'])->toHaveKey('items');
    });
});

describe('GET /api/dependency/stats', function () {
    it('returns dependency stats', function () {
        $this->client->request('GET', '/api/dependency/stats', [], [], $this->authHeader($this->token));

        expect($this->client->getResponse()->getStatusCode())->toBe(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        expect($data['success'])->toBeTrue();
    });
});

describe('POST /api/dependency/sync', function () {
    it('triggers dependency sync and returns 202', function () {
        $this->client->request('POST', '/api/dependency/sync', [], [], $this->authHeader($this->token));

        expect($this->client->getResponse()->getStatusCode())->toBe(202);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        expect($data['data'])->toHaveKey('syncId');
    });
});
```

- [ ] **Step 2: Write VulnerabilityEndpointsTest**

```php
<?php

declare(strict_types=1);

use App\Dependency\Domain\Model\Dependency;
use App\Dependency\Domain\Repository\DependencyRepositoryInterface;
use App\Shared\Domain\ValueObject\DependencyType;
use App\Shared\Domain\ValueObject\PackageManager;
use App\Tests\Helpers\AuthHelper;
use App\Tests\Helpers\DatabaseHelper;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class, AuthHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->client = static::createClient();
    ['token' => $this->token] = $this->createAuthenticatedUser();
});

describe('Vulnerability CRUD', function () {
    it('creates, lists, gets, and updates a vulnerability', function () {
        // Create dependency directly
        $depRepo = self::getContainer()->get(DependencyRepositoryInterface::class);
        $dep = Dependency::create('vuln-pkg', '1.0', '2.0', '1.5', PackageManager::Composer, DependencyType::Runtime, true, Uuid::v7());
        $depRepo->save($dep);
        $depId = $dep->getId()->toRfc4122();

        // Create vulnerability
        $this->client->request('POST', '/api/dependency/vulnerabilities', [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'cveId' => 'CVE-2026-99999',
            'severity' => 'critical',
            'title' => 'Remote code execution',
            'description' => 'Critical RCE vulnerability',
            'patchedVersion' => '2.0.0',
            'dependencyId' => $depId,
        ]));
        expect($this->client->getResponse()->getStatusCode())->toBe(201);
        $vulnId = json_decode($this->client->getResponse()->getContent(), true)['data']['id'];

        // List
        $this->client->request('GET', '/api/dependency/vulnerabilities', [], [], $this->authHeader($this->token));
        expect($this->client->getResponse()->getStatusCode())->toBe(200);

        // Get
        $this->client->request('GET', "/api/dependency/vulnerabilities/{$vulnId}", [], [], $this->authHeader($this->token));
        expect($this->client->getResponse()->getStatusCode())->toBe(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        expect($data['data']['cveId'])->toBe('CVE-2026-99999');

        // Update
        $this->client->request('PUT', "/api/dependency/vulnerabilities/{$vulnId}", [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'status' => 'fixed',
        ]));
        expect($this->client->getResponse()->getStatusCode())->toBe(200);
    });
});
```

- [ ] **Step 3: Run all Dependency functional tests**

```bash
docker compose exec php vendor/bin/pest tests/Functional/Dependency/ --verbose
```
Expected: All tests pass.

- [ ] **Step 4: Commit**

```bash
git add backend/tests/Functional/Dependency/
git commit -m "test(functional): add Dependency endpoint tests — dependencies, vulnerabilities, stats, sync"
```

---

### Task 9: Functional Tests — Activity Endpoints

**Files:**
- Create: `backend/tests/Functional/Activity/ActivityEventEndpointsTest.php`
- Create: `backend/tests/Functional/Activity/NotificationEndpointsTest.php`
- Create: `backend/tests/Functional/Activity/SyncTaskEndpointsTest.php`
- Create: `backend/tests/Functional/Activity/BuildMetricEndpointsTest.php`
- Create: `backend/tests/Functional/Activity/DashboardEndpointsTest.php`

- [ ] **Step 1: Write ActivityEventEndpointsTest**

```php
<?php

declare(strict_types=1);

use App\Tests\Helpers\AuthHelper;
use App\Tests\Helpers\DatabaseHelper;

uses(DatabaseHelper::class, AuthHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->client = static::createClient();
    ['token' => $this->token] = $this->createAuthenticatedUser();
});

describe('Activity Event endpoints', function () {
    it('creates, lists, and gets activity events', function () {
        // Create
        $this->client->request('POST', '/api/activity/events', [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'type' => 'project.created',
            'entityType' => 'Project',
            'entityId' => 'p-123',
            'payload' => ['name' => 'Test'],
            'userId' => 'u-1',
        ]));
        expect($this->client->getResponse()->getStatusCode())->toBe(201);
        $eventId = json_decode($this->client->getResponse()->getContent(), true)['data']['id'];

        // List
        $this->client->request('GET', '/api/activity/events', [], [], $this->authHeader($this->token));
        expect($this->client->getResponse()->getStatusCode())->toBe(200);

        // Get
        $this->client->request('GET', "/api/activity/events/{$eventId}", [], [], $this->authHeader($this->token));
        expect($this->client->getResponse()->getStatusCode())->toBe(200);
    });
});
```

- [ ] **Step 2: Write NotificationEndpointsTest**

```php
<?php

declare(strict_types=1);

use App\Activity\Domain\Model\Notification;
use App\Activity\Domain\Model\NotificationChannel;
use App\Activity\Domain\Repository\NotificationRepositoryInterface;
use App\Tests\Helpers\AuthHelper;
use App\Tests\Helpers\DatabaseHelper;

uses(DatabaseHelper::class, AuthHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->client = static::createClient();
    ['user' => $this->user, 'token' => $this->token] = $this->createAuthenticatedUser();
});

describe('Notification endpoints', function () {
    it('creates a notification and returns 201', function () {
        $this->client->request('POST', '/api/activity/notifications', [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'title' => 'Alert',
            'message' => 'Something happened',
            'channel' => 'in_app',
            'userId' => $this->user->getId()->toRfc4122(),
        ]));

        expect($this->client->getResponse()->getStatusCode())->toBe(201);
    });

    it('lists notifications for current user', function () {
        // Create notification directly
        $repo = self::getContainer()->get(NotificationRepositoryInterface::class);
        $repo->save(Notification::create('N1', 'Msg', NotificationChannel::InApp, $this->user->getId()->toRfc4122()));

        $this->client->request('GET', '/api/activity/notifications', [], [], $this->authHeader($this->token));

        expect($this->client->getResponse()->getStatusCode())->toBe(200);
    });

    it('marks notification as read', function () {
        $repo = self::getContainer()->get(NotificationRepositoryInterface::class);
        $notif = Notification::create('Unread', 'Msg', NotificationChannel::InApp, $this->user->getId()->toRfc4122());
        $repo->save($notif);

        $this->client->request('PUT', '/api/activity/notifications/' . $notif->getId()->toRfc4122(), [], [], $this->authHeader($this->token));

        expect($this->client->getResponse()->getStatusCode())->toBe(200);
    });
});
```

- [ ] **Step 3: Write SyncTaskEndpointsTest**

```php
<?php

declare(strict_types=1);

use App\Activity\Domain\Model\SyncTask;
use App\Activity\Domain\Model\SyncTaskSeverity;
use App\Activity\Domain\Model\SyncTaskType;
use App\Activity\Domain\Repository\SyncTaskRepositoryInterface;
use App\Tests\Helpers\AuthHelper;
use App\Tests\Helpers\DatabaseHelper;
use Symfony\Component\Uid\Uuid;

uses(DatabaseHelper::class, AuthHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->client = static::createClient();
    ['token' => $this->token] = $this->createAuthenticatedUser();
});

describe('Sync Task endpoints', function () {
    it('lists sync tasks with filters', function () {
        $repo = self::getContainer()->get(SyncTaskRepositoryInterface::class);
        $repo->save(SyncTask::create(SyncTaskType::Vulnerability, SyncTaskSeverity::High, 'CVE', 'Desc', Uuid::v7(), ['cveId' => 'CVE-001']));

        $this->client->request('GET', '/api/activity/sync-tasks?status=open', [], [], $this->authHeader($this->token));

        expect($this->client->getResponse()->getStatusCode())->toBe(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        expect($data['data']['items'])->toHaveCount(1);
    });

    it('gets a sync task', function () {
        $repo = self::getContainer()->get(SyncTaskRepositoryInterface::class);
        $task = SyncTask::create(SyncTaskType::StalePr, SyncTaskSeverity::Medium, 'Stale', 'Desc', Uuid::v7(), []);
        $repo->save($task);

        $this->client->request('GET', '/api/activity/sync-tasks/' . $task->getId()->toRfc4122(), [], [], $this->authHeader($this->token));

        expect($this->client->getResponse()->getStatusCode())->toBe(200);
    });

    it('updates sync task status', function () {
        $repo = self::getContainer()->get(SyncTaskRepositoryInterface::class);
        $task = SyncTask::create(SyncTaskType::OutdatedDependency, SyncTaskSeverity::Low, 'Old', 'Desc', Uuid::v7(), []);
        $repo->save($task);

        $this->client->request('PATCH', '/api/activity/sync-tasks/' . $task->getId()->toRfc4122(), [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'status' => 'acknowledged',
        ]));

        expect($this->client->getResponse()->getStatusCode())->toBe(200);
    });

    it('gets sync task stats', function () {
        $this->client->request('GET', '/api/activity/sync-tasks/stats', [], [], $this->authHeader($this->token));

        expect($this->client->getResponse()->getStatusCode())->toBe(200);
    });
});
```

- [ ] **Step 4: Write BuildMetricEndpointsTest**

```php
<?php

declare(strict_types=1);

use App\Tests\Helpers\AuthHelper;
use App\Tests\Helpers\DatabaseHelper;

uses(DatabaseHelper::class, AuthHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->client = static::createClient();
    ['user' => $this->user, 'token' => $this->token] = $this->createAuthenticatedUser();
});

describe('Build Metric endpoints', function () {
    it('creates, lists, and gets latest build metric', function () {
        // Create project first
        $this->client->request('POST', '/api/catalog/projects', [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'name' => 'BM Project', 'slug' => 'bm-project', 'repositoryUrl' => 'https://github.com/t/bm',
            'defaultBranch' => 'main', 'visibility' => 'private', 'ownerId' => $this->user->getId()->toRfc4122(),
        ]));
        $projectId = json_decode($this->client->getResponse()->getContent(), true)['data']['id'];

        // Create build metric
        $this->client->request('POST', "/api/activity/projects/{$projectId}/build-metrics", [], [], array_merge($this->authHeader($this->token), ['CONTENT_TYPE' => 'application/json']), json_encode([
            'duration' => 120,
            'success' => true,
            'coverage' => 85.5,
            'mutationScore' => 80.0,
            'branch' => 'main',
            'commitSha' => 'abc123',
        ]));
        expect($this->client->getResponse()->getStatusCode())->toBe(201);

        // List
        $this->client->request('GET', "/api/activity/projects/{$projectId}/build-metrics", [], [], $this->authHeader($this->token));
        expect($this->client->getResponse()->getStatusCode())->toBe(200);

        // Get latest
        $this->client->request('GET', "/api/activity/projects/{$projectId}/build-metrics/latest", [], [], $this->authHeader($this->token));
        expect($this->client->getResponse()->getStatusCode())->toBe(200);
    });
});
```

- [ ] **Step 5: Write DashboardEndpointsTest**

```php
<?php

declare(strict_types=1);

use App\Tests\Helpers\AuthHelper;
use App\Tests\Helpers\DatabaseHelper;

uses(DatabaseHelper::class, AuthHelper::class);

beforeEach(function () {
    $this->resetDatabase();
    $this->client = static::createClient();
    ['token' => $this->token] = $this->createAuthenticatedUser();
});

describe('GET /api/activity/dashboard', function () {
    it('returns dashboard data for authenticated user', function () {
        $this->client->request('GET', '/api/activity/dashboard', [], [], $this->authHeader($this->token));

        expect($this->client->getResponse()->getStatusCode())->toBe(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        expect($data['success'])->toBeTrue();
    });

    it('returns 401 without auth', function () {
        $this->client->request('GET', '/api/activity/dashboard');
        expect($this->client->getResponse()->getStatusCode())->toBe(401);
    });
});
```

- [ ] **Step 6: Run all Activity functional tests**

```bash
docker compose exec php vendor/bin/pest tests/Functional/Activity/ --verbose
```
Expected: All tests pass.

- [ ] **Step 7: Commit**

```bash
git add backend/tests/Functional/Activity/
git commit -m "test(functional): add Activity endpoint tests — events, notifications, sync tasks, build metrics, dashboard"
```

---

### Task 10: Functional Tests — Shared Endpoints (Health)

**Files:**
- Create: `backend/tests/Functional/Shared/HealthEndpointsTest.php`

- [ ] **Step 1: Write HealthEndpointsTest**

```php
<?php

declare(strict_types=1);

describe('GET /healthz', function () {
    it('returns healthy status', function () {
        $client = static::createClient();
        $client->request('GET', '/healthz');

        expect($client->getResponse()->getStatusCode())->toBe(200);

        $data = json_decode($client->getResponse()->getContent(), true);
        expect($data['status'])->toBe('healthy');
        expect($data['checks']['database'])->toBe('ok');
    });
});

describe('GET /readyz', function () {
    it('returns ready status', function () {
        $client = static::createClient();
        $client->request('GET', '/readyz');

        expect($client->getResponse()->getStatusCode())->toBe(200);

        $data = json_decode($client->getResponse()->getContent(), true);
        expect($data['status'])->toBe('ready');
    });
});
```

- [ ] **Step 2: Run to verify**

```bash
docker compose exec php vendor/bin/pest tests/Functional/Shared/ --verbose
```
Expected: 2 tests pass.

- [ ] **Step 3: Commit**

```bash
git add backend/tests/Functional/Shared/
git commit -m "test(functional): add health and readiness endpoint tests"
```

---

### Task 11: Run Full Suite & Verify Coverage

- [ ] **Step 1: Run the full test suite**

```bash
docker compose exec php vendor/bin/pest --verbose
```
Expected: All tests pass (unit + integration + functional).

- [ ] **Step 2: Run coverage check**

```bash
docker compose exec php vendor/bin/pest --coverage --min=80
```
Expected: Coverage >= 80%.

- [ ] **Step 3: Run mutation testing**

```bash
docker compose exec php vendor/bin/infection --min-msi=80 --min-covered-msi=80 --threads=4
```
Expected: MSI >= 80%.

- [ ] **Step 4: Fix any failing tests or coverage gaps**

If coverage or MSI drops below 80%, add targeted tests for the uncovered areas. Common gaps:
- Exception paths in repositories (connection errors)
- Edge cases in pagination (page 0, negative perPage)
- Validation error paths in controllers

- [ ] **Step 5: Final commit**

```bash
git add -A backend/tests/
git commit -m "test: complete Phase 1 — integration and functional test suites with 80%+ coverage and MSI"
```

---

## Summary

| Suite | Files | Estimated Tests |
|-------|-------|----------------|
| Integration — Identity | 2 | 11 |
| Integration — Catalog | 5 | ~25 |
| Integration — Dependency | 3 | ~17 |
| Integration — Activity | 4 | ~18 |
| Functional — Identity | 3 | ~15 |
| Functional — Catalog | 4 | ~15 |
| Functional — Dependency | 2 | ~8 |
| Functional — Activity | 5 | ~12 |
| Functional — Shared | 1 | 2 |
| **Total new** | **29** | **~123** |

Combined with existing 144 unit test files (1020 cases), the total test count will be ~1143 across all suites.
