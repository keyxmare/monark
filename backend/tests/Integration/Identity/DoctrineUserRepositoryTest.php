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
